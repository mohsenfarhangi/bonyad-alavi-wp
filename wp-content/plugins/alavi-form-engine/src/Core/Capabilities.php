<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Core;

final class Capabilities
{
    public const ACCESS_ADMIN = 'afe_access_admin';
    public const MANAGE_FORMS = 'afe_manage_forms';
    public const MANAGE_SETTINGS = 'afe_manage_settings';
    public const VIEW_SUBMISSIONS = 'afe_view_submissions';
    public const EDIT_SUBMISSIONS = 'afe_edit_submissions';
    public const DELETE_SUBMISSIONS = 'afe_delete_submissions';
    public const CHANGE_STATUS = 'afe_change_submission_status';
    public const VIEW_FILES = 'afe_view_submission_files';
    public const ADD_NOTES = 'afe_add_submission_notes';
    public const EXPORT = 'afe_export_submissions';
    public const VIEW_REPORTS = 'afe_view_reports';
    public const MANAGE_DATABASE = 'afe_manage_database';
    public const API_SUBMIT = 'afe_api_submit';

    public static function all(): array
    {
        return [
            self::ACCESS_ADMIN,
            self::MANAGE_FORMS,
            self::MANAGE_SETTINGS,
            self::VIEW_SUBMISSIONS,
            self::EDIT_SUBMISSIONS,
            self::DELETE_SUBMISSIONS,
            self::CHANGE_STATUS,
            self::VIEW_FILES,
            self::ADD_NOTES,
            self::EXPORT,
            self::VIEW_REPORTS,
            self::MANAGE_DATABASE,
            self::API_SUBMIT,
        ];
    }

    public static function syncAccess(): void
    {
        foreach(wp_roles()->roles as $roleKey=>$data){
            $role=get_role($roleKey); if(!$role) continue;
            if($roleKey==='administrator'){ $role->add_cap(self::ACCESS_ADMIN); continue; }
            $hasAny=false;
            foreach(self::all() as $cap){
                if($cap===self::ACCESS_ADMIN) continue;
                if($role->has_cap($cap)){ $hasAny=true; break; }
            }
            if($hasAny) $role->add_cap(self::ACCESS_ADMIN); else $role->remove_cap(self::ACCESS_ADMIN);
        }
    }

    public static function assignable(): array
    {
        return array_values(array_filter(self::all(),static fn(string $cap): bool => $cap!==self::ACCESS_ADMIN));
    }

    public static function install(): void
    {
        $admin = get_role('administrator');
        if ($admin) {
            foreach (self::all() as $cap) {
                $admin->add_cap($cap);
            }
        }
    }
}
