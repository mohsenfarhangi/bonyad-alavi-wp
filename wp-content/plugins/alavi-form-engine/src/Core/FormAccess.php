<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Core;

use BonyadAlavi\FormEngine\Form\FormRegistry;

/** Per-form capability registry and authorization helper. */
final class FormAccess
{
    public const VIEW='view';
    public const EDIT='edit';
    public const MANAGE='manage';
    public const EXPORT='export';
    public const REPORTS='reports';
    public const CONFIGURE='configure';

    /** @return array<string,string> */
    public static function levels(): array
    {
        return [
            self::VIEW=>'مشاهده اطلاعات',
            self::EDIT=>'ویرایش اطلاعات',
            self::MANAGE=>'مدیریت عملیات',
            self::EXPORT=>'خروجی / چاپ',
            self::REPORTS=>'گزارش و آمار',
            self::CONFIGURE=>'تنظیمات فرم',
        ];
    }

    public static function capability(string $formSlug,string $level): string
    {
        $slug=str_replace('-','_',sanitize_key($formSlug));
        $level=array_key_exists($level,self::levels())?$level:self::VIEW;
        return 'afe_form_'.$slug.'_'.$level;
    }

    public function can(string $formSlug,string $level): bool
    {
        $global=$this->globalCapability($level);
        if($global!=='' && !current_user_can($global)) return false;
        if($level===self::VIEW){
            foreach([self::VIEW,self::EDIT,self::MANAGE,self::EXPORT] as $candidate){
                if(current_user_can(self::capability($formSlug,$candidate))) return true;
            }
            return false;
        }
        return current_user_can(self::capability($formSlug,$level));
    }

    /** @return array<string,\BonyadAlavi\FormEngine\Form\Form> */
    public function accessibleForms(FormRegistry $registry,string $level): array
    {
        return array_filter($registry->all(),fn($form)=>$this->can($form->slug(),$level));
    }

    /** Initialize dynamic capabilities whenever forms are registered. */
    public function sync(FormRegistry $registry): void
    {
        $initialized=(array)get_option('afe_initialized_form_caps',[]);
        $firstMigration=!get_option('afe_form_access_migrated',false);

        foreach($registry->all() as $slug=>$form){
            $admin=get_role('administrator');
            foreach(self::levels() as $level=>$label){
                $cap=self::capability($slug,$level);
                if($admin) $admin->add_cap($cap);

                $done=is_array($initialized[$slug]??null) && !empty($initialized[$slug][$level]);
                if($done) continue;

                // Compatibility migration: only the forms that exist when this
                // permission model is first installed inherit legacy role access.
                // Forms registered later default to Administrator-only until an
                // administrator explicitly grants their per-form capabilities.
                if($firstMigration){
                    foreach(wp_roles()->roles as $roleKey=>$roleData){
                        if($roleKey==='administrator') continue;
                        $role=get_role($roleKey); if(!$role) continue;
                        $global=$this->globalCapability($level);
                        $legacyAllowed=$global!=='' && $role->has_cap($global);
                        if($level===self::MANAGE){
                            $legacyAllowed=$role->has_cap(Capabilities::CHANGE_STATUS)
                                ||$role->has_cap(Capabilities::VIEW_FILES)
                                ||$role->has_cap(Capabilities::ADD_NOTES)
                                ||$role->has_cap(Capabilities::DELETE_SUBMISSIONS);
                        }
                        if($legacyAllowed) $role->add_cap($cap);
                    }
                }

                if(!isset($initialized[$slug]) || !is_array($initialized[$slug])) $initialized[$slug]=[];
                $initialized[$slug][$level]=1;
            }
        }

        update_option('afe_initialized_form_caps',$initialized,false);
        if($firstMigration) update_option('afe_form_access_migrated',1,false);
    }

    public function globalCapability(string $level): string
    {
        return match($level){
            self::VIEW=>Capabilities::VIEW_SUBMISSIONS,
            self::EDIT=>Capabilities::EDIT_SUBMISSIONS,
            self::MANAGE=>'',
            self::EXPORT=>Capabilities::EXPORT,
            self::REPORTS=>Capabilities::VIEW_REPORTS,
            self::CONFIGURE=>Capabilities::MANAGE_FORMS,
            default=>'',
        };
    }
}
