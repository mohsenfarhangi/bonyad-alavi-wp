<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Rest;

use BonyadAlavi\FormEngine\Core\Capabilities;
use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Submission\SubmissionService;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

final class ApiController
{
    private const NS = 'alavi-form-engine/v1';

    public function __construct(
        private readonly FormRegistry $registry,
        private readonly SubmissionRepository $submissions,
        private readonly SubmissionService $service,
        private readonly FormAccess $access
    ) {}

    public function registerRoutes(): void
    {
        register_rest_route(self::NS,'/forms',[
            'methods'=>'GET',
            'callback'=>[$this,'forms'],
            'permission_callback'=>[$this,'canReadForms'],
        ]);
        register_rest_route(self::NS,'/forms/(?P<slug>[a-z0-9\-]+)',[
            'methods'=>'GET',
            'callback'=>[$this,'form'],
            'permission_callback'=>[$this,'canReadForms'],
        ]);
        register_rest_route(self::NS,'/submissions',[
            'methods'=>'GET',
            'callback'=>[$this,'submissions'],
            'permission_callback'=>static fn()=>current_user_can(Capabilities::VIEW_SUBMISSIONS),
        ]);
        register_rest_route(self::NS,'/forms/(?P<slug>[a-z0-9\-]+)/submit',[
            'methods'=>'POST',
            'callback'=>[$this,'submitForm'],
            'permission_callback'=>static fn()=>current_user_can(Capabilities::API_SUBMIT),
        ]);
        register_rest_route(self::NS,'/submissions/(?P<id>\d+)',[
            [
                'methods'=>'GET',
                'callback'=>[$this,'submission'],
                'permission_callback'=>static fn()=>current_user_can(Capabilities::VIEW_SUBMISSIONS),
            ],
            [
                'methods'=>'PATCH',
                'callback'=>[$this,'updateSubmission'],
                'permission_callback'=>static fn()=>current_user_can(Capabilities::EDIT_SUBMISSIONS),
            ],
        ]);
    }

    public function canReadForms(): bool
    {
        $settings=get_option('afe_settings',[]);
        return !empty($settings['api_public_forms']) || current_user_can(Capabilities::MANAGE_FORMS);
    }

    public function forms(): WP_REST_Response
    {
        $out=[];
        $settings=get_option('afe_settings',[]);
        $forms=!empty($settings['api_public_forms'])?$this->registry->all():$this->access->accessibleForms($this->registry,FormAccess::CONFIGURE);
        foreach($forms as $slug=>$form) {
            $def=$this->service->resolvedForm($slug);
            $out[]=['slug'=>$slug,'title'=>$def['title'],'description'=>$def['description'],'settings'=>$this->publicSettings($def['settings'])];
        }
        return new WP_REST_Response($out,200);
    }

    public function form(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $slug=sanitize_key((string)$request['slug']);
        if(!$this->registry->has($slug)) return new WP_Error('afe_not_found','Form not found',['status'=>404]);
        $settings=get_option('afe_settings',[]);
        if(empty($settings['api_public_forms']) && !$this->access->can($slug,FormAccess::CONFIGURE)) return new WP_Error('afe_forbidden','Form access denied',['status'=>403]);
        $def=$this->service->resolvedForm($slug);
        unset($def['actions']);
        return new WP_REST_Response($def,200);
    }

    public function submitForm(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $slug=sanitize_key((string)$request['slug']);
        if(!$this->registry->has($slug)) return new WP_Error('afe_not_found','Form not found',['status'=>404]);

        $payload=(array)$request->get_json_params();
        $data=$payload['data']??$request->get_param('data')??[];
        if(is_string($data)) {
            $decoded=json_decode($data,true);
            $data=is_array($decoded)?$decoded:[];
        }
        if(!is_array($data)) return new WP_Error('afe_invalid_data','data must be an object/array',['status'=>400]);
        $draft=!empty($payload['draft']) || filter_var($request->get_param('draft'),FILTER_VALIDATE_BOOL);

        $result=$this->service->submitApi($slug,$data,$draft);
        if(is_wp_error($result)) {
            $code=$result->get_error_code();
            $status=in_array($code,['validation','duplicate'],true)?422:400;
            $extra=(array)$result->get_error_data();
            return new WP_Error($code,$result->get_error_message(),[
                'status'=>$status,
                'errors'=>$code==='validation'?$extra:[],
                'duplicate'=>$code==='duplicate',
            ]);
        }
        return new WP_REST_Response($result,201);
    }

    public function submissions(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $formSlug=sanitize_key((string)$request->get_param('form_slug'));
        $accessible=$this->access->accessibleForms($this->registry,FormAccess::VIEW);
        if($formSlug!=='' && !isset($accessible[$formSlug])) return new WP_Error('afe_forbidden','Form access denied',['status'=>403]);
        $result=$this->submissions->list([
            'form_slug'=>$formSlug,
            'form_slugs'=>array_keys($accessible),
            'status'=>sanitize_key((string)$request->get_param('status')),
            'q'=>sanitize_text_field((string)$request->get_param('q')),
            'from'=>sanitize_text_field((string)$request->get_param('from')),
            'to'=>sanitize_text_field((string)$request->get_param('to')),
        ],max(1,(int)$request->get_param('page')),min(100,max(1,(int)($request->get_param('per_page')?:20))));
        return new WP_REST_Response($result,200);
    }

    public function submission(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $row=$this->submissions->find((int)$request['id']);
        if(!$row) return new WP_Error('afe_not_found','Submission not found',['status'=>404]);
        $formSlug=(string)$row['form_slug'];
        if(!$this->access->can($formSlug,FormAccess::VIEW)) return new WP_Error('afe_forbidden','Form access denied',['status'=>403]);
        if($this->access->can($formSlug,FormAccess::MANAGE) && current_user_can(Capabilities::VIEW_FILES)) {
            $row['files']=$this->submissions->files((int)$row['id']);
        }
        if($this->access->can($formSlug,FormAccess::MANAGE)) {
            $row['notes']=$this->submissions->notes((int)$row['id']);
        }
        unset($row['edit_token_hash'],$row['ip_hash']);
        return new WP_REST_Response($row,200);
    }

    public function updateSubmission(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $row=$this->submissions->find((int)$request['id']);
        if(!$row) return new WP_Error('afe_not_found','Submission not found',['status'=>404]);
        if(!$this->access->can((string)$row['form_slug'],FormAccess::EDIT)) return new WP_Error('afe_forbidden','Form access denied',['status'=>403]);
        $payload=(array)$request->get_json_params();
        $changes=['updated_at'=>current_time('mysql',true)];
        if(isset($payload['status']) && $this->access->can((string)$row['form_slug'],FormAccess::MANAGE) && current_user_can(Capabilities::CHANGE_STATUS)) {
            $form=$this->service->resolvedForm($row['form_slug']);
            $status=sanitize_key((string)$payload['status']);
            if(isset($form['workflow'][$status])) $changes['status']=$status;
        }
        if(isset($payload['data']) && is_array($payload['data'])) {
            $data=$this->sanitizeRecursive($payload['data']);
            $changes['data_json']=wp_json_encode($data,JSON_UNESCAPED_UNICODE);
            $this->submissions->replaceValues((int)$row['id'],$data);
        }
        $this->submissions->update((int)$row['id'],$changes);
        $this->submissions->audit((int)$row['id'],$row['form_slug'],'api.updated',array_keys($changes));
        return new WP_REST_Response($this->submissions->find((int)$row['id']),200);
    }

    private function publicSettings(array $settings): array
    {
        return array_intersect_key($settings,array_flip(['wizard','save_draft','show_progress','editing_enabled','editing_modes','captcha']));
    }

    private function sanitizeRecursive(mixed $value): mixed
    {
        if(is_array($value)) {
            $out=[]; foreach($value as $k=>$v) $out[sanitize_key((string)$k)]=$this->sanitizeRecursive($v); return $out;
        }
        return is_scalar($value)?sanitize_textarea_field((string)$value):'';
    }
}
