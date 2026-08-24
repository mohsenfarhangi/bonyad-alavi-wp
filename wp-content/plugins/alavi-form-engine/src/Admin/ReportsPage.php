<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Admin;

use BonyadAlavi\FormEngine\Core\Capabilities;
use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Localization\LocaleDateService;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;

final class ReportsPage
{
    public function __construct(private readonly FormRegistry $registry, private readonly SubmissionRepository $repo, private readonly FormAccess $access, private readonly LocaleDateService $dates) {}

    public function render(): void
    {
        if(!current_user_can(Capabilities::VIEW_REPORTS)) wp_die('دسترسی کافی ندارید.');

        $filters=[
            'form_slug'=>sanitize_key(wp_unslash($_GET['form_slug']??'')),
            'status'=>sanitize_key(wp_unslash($_GET['status']??'')),
            'province'=>sanitize_text_field(wp_unslash($_GET['province']??'')),
            'group_nature'=>sanitize_text_field(wp_unslash($_GET['group_nature']??'')),
            'license_issuer'=>sanitize_text_field(wp_unslash($_GET['license_issuer']??'')),
            'activity'=>sanitize_text_field(wp_unslash($_GET['activity']??'')),
            'from'=>sanitize_text_field(wp_unslash($_GET['from']??'')),
            'to'=>sanitize_text_field(wp_unslash($_GET['to']??'')),
        ];
        $accessible=$this->access->accessibleForms($this->registry,FormAccess::REPORTS);
        if($filters['form_slug']!=='' && !isset($accessible[$filters['form_slug']])) wp_die('به گزارش این فرم دسترسی ندارید.');
        $filters['form_slugs']=array_keys($accessible);
        $filters['from_utc']=$this->dates->filterBoundary($filters['from'],false)??'';
        $filters['to_utc']=$this->dates->filterBoundary($filters['to'],true)??'';

        [$where,$args]=$this->where($filters);
        global $wpdb;
        $whereSql=implode(' AND ',$where);

        $countSql="SELECT s.status,COUNT(*) total FROM {$wpdb->prefix}afe_submissions s WHERE {$whereSql} GROUP BY s.status";
        $countRows=$wpdb->get_results($args?$wpdb->prepare($countSql,...$args):$countSql,ARRAY_A)?:[];
        $counts=[]; foreach($countRows as $row) $counts[$row['status']]=(int)$row['total'];

        $labels=['draft'=>'پیش‌نویس','new'=>'جدید','review'=>'در حال بررسی','revision'=>'نیاز به اصلاح','approved'=>'تأیید شده','rejected'=>'رد شده'];
        $total=array_sum($counts);
        $fieldLabels=$this->fieldOptionMaps($filters['form_slug'],$accessible);
        $provinceOptions=$wpdb->get_results("SELECT id,name FROM {$wpdb->prefix}afe_geo_provinces ORDER BY name",ARRAY_A)?:[];

        echo '<div class="wrap afe-admin-wrap"><h1>گزارش و آمار</h1>';
        echo '<form class="afe-report-filters" method="get"><input type="hidden" name="page" value="alavi-form-engine-reports">';
        echo '<label>فرم<select name="form_slug"><option value="">همه فرم‌ها</option>';
        foreach($accessible as $key=>$form) echo '<option value="'.esc_attr($key).'" '.selected($filters['form_slug'],$key,false).'>'.esc_html($form->toArray()['title']).'</option>';
        echo '</select></label>';
        echo '<label>وضعیت<select name="status"><option value="">همه</option>'; foreach($labels as $k=>$v) echo '<option value="'.esc_attr($k).'" '.selected($filters['status'],$k,false).'>'.esc_html($v).'</option>'; echo '</select></label>';
        echo '<label>استان<select name="province"><option value="">همه</option>'; foreach($provinceOptions as $province) echo '<option value="'.esc_attr((string)$province['id']).'" '.selected($filters['province'],(string)$province['id'],false).'>'.esc_html($province['name']).'</option>'; echo '</select></label>';
        echo $this->selectFilter('ماهیت گروه','group_nature',$filters['group_nature'],$fieldLabels['group_nature']??[]);
        echo $this->selectFilter('مرجع مجوز','license_issuer',$filters['license_issuer'],$fieldLabels['license_issuer']??[]);
        echo $this->selectFilter('عرصه فعالیت','activity',$filters['activity'],$fieldLabels['activity']??[]);
        echo $this->dateFilterInput('from',$filters['from'],'از تاریخ').$this->dateFilterInput('to',$filters['to'],'تا تاریخ');
        echo '<div class="afe-report-filter-actions"><button class="button button-primary">اعمال فیلتر</button><a class="button" href="'.esc_url(admin_url('admin.php?page=alavi-form-engine-reports')).'">پاک کردن</a></div></form>';

        echo '<div class="afe-stat-grid"><div class="afe-stat"><span>کل ثبت‌ها</span><strong>'.number_format_i18n($total).'</strong></div>';
        foreach($labels as $k=>$label) echo '<div class="afe-stat"><span>'.esc_html($label).'</span><strong>'.number_format_i18n((int)($counts[$k]??0)).'</strong></div>';
        echo '</div>';

        // Distribution queries keep the same submission filters and only add their own grouping value.
        $provinceSql="SELECT COALESCE(g.name,v.value_text) label,COUNT(DISTINCT s.id) total
            FROM {$wpdb->prefix}afe_submissions s
            INNER JOIN {$wpdb->prefix}afe_submission_values v ON v.submission_id=s.id AND v.field_key='province'
            LEFT JOIN {$wpdb->prefix}afe_geo_provinces g ON CAST(g.id AS CHAR)=v.value_text
            WHERE {$whereSql} GROUP BY v.value_text,g.name ORDER BY total DESC LIMIT 12";
        $topProvinces=$wpdb->get_results($args?$wpdb->prepare($provinceSql,...$args):$provinceSql,ARRAY_A)?:[];

        $natureSql="SELECT v.value_text label,COUNT(DISTINCT s.id) total
            FROM {$wpdb->prefix}afe_submissions s
            INNER JOIN {$wpdb->prefix}afe_submission_values v ON v.submission_id=s.id AND v.field_key='group_nature'
            WHERE {$whereSql} GROUP BY v.value_text ORDER BY total DESC LIMIT 12";
        $natures=$wpdb->get_results($args?$wpdb->prepare($natureSql,...$args):$natureSql,ARRAY_A)?:[];
        foreach($natures as &$row) $row['label']=$fieldLabels['group_nature'][$row['label']]??$row['label']; unset($row);

        $activitySql="SELECT v.value_text label,COUNT(DISTINCT s.id) total
            FROM {$wpdb->prefix}afe_submissions s
            INNER JOIN {$wpdb->prefix}afe_submission_values v ON v.submission_id=s.id
              AND v.field_key IN ('activity_priority_1','activity_priority_2','activity_priority_3')
            WHERE {$whereSql} GROUP BY v.value_text ORDER BY total DESC LIMIT 12";
        $activities=$wpdb->get_results($args?$wpdb->prepare($activitySql,...$args):$activitySql,ARRAY_A)?:[];
        foreach($activities as &$row) $row['label']=$fieldLabels['activity'][$row['label']]??$row['label']; unset($row);

        echo '<div class="afe-report-grid"><div class="afe-admin-card"><h2>استان ثبت گروه</h2>'.$this->barTable($topProvinces).'</div>';
        echo '<div class="afe-admin-card"><h2>ماهیت گروه‌ها</h2>'.$this->barTable($natures).'</div>';
        echo '<div class="afe-admin-card"><h2>عرصه‌های فعالیت</h2>'.$this->barTable($activities).'</div></div></div>';
    }

    private function where(array $filters): array
    {
        global $wpdb;
        $where=['s.trashed_at IS NULL']; $args=[];
        $allowed=array_values(array_filter(array_map('sanitize_key',(array)($filters['form_slugs']??[]))));
        if(array_key_exists('form_slugs',$filters) && !$allowed) $where[]='1=0';
        elseif($allowed){ $where[]='s.form_slug IN ('.implode(',',array_fill(0,count($allowed),'%s')).')'; array_push($args,...$allowed); }
        if($filters['form_slug']!==''){ $where[]='s.form_slug=%s'; $args[]=$filters['form_slug']; }
        if($filters['status']!==''){ $where[]='s.status=%s'; $args[]=$filters['status']; }
        if(($filters['from_utc']??'')!==''){ $where[]='s.created_at>=%s'; $args[]=$filters['from_utc']; }
        if(($filters['to_utc']??'')!==''){ $where[]='s.created_at<=%s'; $args[]=$filters['to_utc']; }
        if($filters['province']!==''){
            $where[]="EXISTS (SELECT 1 FROM {$wpdb->prefix}afe_submission_values vf WHERE vf.submission_id=s.id AND vf.field_key='province' AND vf.value_text=%s)";
            $args[]=$filters['province'];
        }
        if($filters['group_nature']!==''){
            $where[]="EXISTS (SELECT 1 FROM {$wpdb->prefix}afe_submission_values vf WHERE vf.submission_id=s.id AND vf.field_key='group_nature' AND vf.value_text=%s)";
            $args[]=$filters['group_nature'];
        }
        if($filters['license_issuer']!==''){
            $where[]="EXISTS (SELECT 1 FROM {$wpdb->prefix}afe_submission_values vf WHERE vf.submission_id=s.id AND vf.field_key='license_issuer' AND vf.value_text=%s)";
            $args[]=$filters['license_issuer'];
        }
        if($filters['activity']!==''){
            $where[]="EXISTS (SELECT 1 FROM {$wpdb->prefix}afe_submission_values vf WHERE vf.submission_id=s.id AND vf.field_key IN ('activity_priority_1','activity_priority_2','activity_priority_3') AND vf.value_text=%s)";
            $args[]=$filters['activity'];
        }
        return [$where,$args];
    }

    private function fieldOptionMaps(string $formSlug,array $accessible): array
    {
        $maps=['group_nature'=>[],'license_issuer'=>[],'activity'=>[]];
        $forms=$formSlug!=='' && isset($accessible[$formSlug]) ? [$accessible[$formSlug]] : $accessible;
        foreach($forms as $form) {
            foreach($form->toArray()['steps'] as $step) {
                foreach($step['items'] as $field) {
                    $name=$field['name']??'';
                    if($name==='group_nature') $maps['group_nature']+=(array)($field['options']??[]);
                    if($name==='license_issuer') $maps['license_issuer']+=(array)($field['options']??[]);
                    if(in_array($name,['activity_priority_1','activity_priority_2','activity_priority_3'],true)) $maps['activity']+=(array)($field['options']??[]);
                }
            }
        }
        return $maps;
    }

    private function dateFilterInput(string $name,string $value,string $label): string
    {
        $jalali=$this->dates->isJalali();
        return '<label>'.esc_html($label).'<input type="'.($jalali?'text':'date').'" class="afe-admin-date" data-afe-admin-date="1" '.($jalali?'data-jdp ':'').'autocomplete="off" name="'.esc_attr($name).'" value="'.esc_attr($value).'" placeholder="'.esc_attr($this->dates->filterPlaceholder()).'"></label>';
    }

    private function selectFilter(string $label,string $name,string $selected,array $options): string
    {
        $html='<label>'.esc_html($label).'<select name="'.esc_attr($name).'"><option value="">همه</option>';
        foreach($options as $k=>$v) {
            if(is_int($k)) $k=$v;
            $html.='<option value="'.esc_attr((string)$k).'" '.selected($selected,(string)$k,false).'>'.esc_html((string)$v).'</option>';
        }
        return $html.'</select></label>';
    }

    private function barTable(array $rows): string
    {
        if(!$rows) return '<p>داده کافی وجود ندارد.</p>';
        $max=max(array_map(static fn($r)=>(int)$r['total'],$rows));
        $html='<div class="afe-bars">';
        foreach($rows as $row) {
            $pct=$max?round(((int)$row['total']/$max)*100):0;
            $html.='<div class="afe-bar-row"><div><span>'.esc_html((string)$row['label']).'</span><strong>'.number_format_i18n((int)$row['total']).'</strong></div><i><b style="width:'.esc_attr((string)$pct).'%"></b></i></div>';
        }
        return $html.'</div>';
    }
}
