<?php

namespace Pantheon\Terminus\Commands\Workflow\Info;

class WorkflowType
{
    /**
     * Workflow Types
     */
    public const ADD_SITE_ORGANIZATION_MEMBERSHIP     = "add_site_organization_membership";
    public const APPLY_UPSTREAM_UPDATES               = "apply_upstream_updates";
    public const AUTOPILOT_VRT                        = "autopilot_vrt";
    public const CHANGE_ENVIRONMENT_IMAGE             = "change_environment_image";
    public const CLEAR_CACHE                          = "clear_cache";
    public const CLEAR_CODE_CACHE                     = "clear_code_cache";
    public const CLONE_DATABASE                       = "clone_database";
    public const CLONE_FILES                          = "clone_files";
    public const CONVERGE_ENVIRONMENT                 = "converge_environment";
    public const CREATE_ENVIRONMENT                   = "create_environment";
    public const CREATE_SITE                          = "create_site";
    public const CREATE_ORGANIZATION                  = "create_organization";
    public const CREATE_MULTIDEV                      = "create_cloud_development_environment";
    public const DELETE_ENVIRONMENT_BRANCH            = "delete_environment_branch";
    public const DETECT_FRAMEWORK                     = "detect_framework";
    public const DELETE_MULTIDEV                      = "delete_cloud_development_environment";
    public const DEPLOY_ENV                           = "deploy";
    public const ENABLE_GIT_MODE                      = "enable_git_mode";
    public const ENABLE_ON_SERVER_DEVELOPMENT         = "enable_on_server_development";
    public const ENV_COMMIT                           = "commit_and_push_on_server_changes";
    public const ENV_UPDATE_PANTHEON_YML              = "environment_update_pantheon_yml";
    public const ENV_CHECK_DATABASE_VERSION           = "check_database_version";
    public const ENV_CHANGE_DATABASE_VERSION          = "change_database_version";
    public const ENV_CHANGE_OBJECT_CACHE_VERSION      = "change_object_cache_version";
    public const ENV_CHANGE_SEARCH_VERSION            = "change_search_version";
    public const MERGE_TO_DEV                         = "merge_cloud_development_environment_into_dev";
    public const MIGRATE_BINDING                      = "migrate_binding_for_site";
    public const REFRESH_CMSMODULES                   = "refresh_cms_modules";
    public const REMOVE_SITE_ORGANIZATION_MEMBERSHIP  = "remove_site_organization_membership";
    public const RESET_BRANCH                         = "reset_branch";
    public const RUN_AUTOPILOT_JOB                    = "run_autopilot_job";
    public const RUN_COMMAND                          = "run_command";
    public const UPDATE_SITE_SETTING                  = "update_site_setting";
    public const UPDATE_SITE_STATUS                   = "update_site_status";

    public const LABELS = [
        "add_site_organization_membership"             => "Add Site Organization Membership",
        "apply_upstream_updates"                       => "Apply Upstream Updates",
        "autopilot_vrt"                                => "Autopilot VRT",
        "change_environment_image"                     => "Change Environment Image",
        "clear_cache"                                  => "Clear Cache",
        "clear_code_cache"                             => "Clear Code Cache",
        "clone_database"                               => "Clone Database",
        "clone_files"                                  => "Clone Files",
        "converge_environment"                         => "Converge Environment",
        "create_environment"                           => "Create Environment",
        "create_site"                                  => "Create Site",
        "create_organization"                          => "Create Organization",
        "create_cloud_development_environment"         => "Create Cloud Development Environment",
        "delete_environment_branch"                    => "Delete Environment Branch",
        "detect_framework"                             => "Detect Framework",
        "delete_cloud_development_environment"         => "Delete Cloud Development Environment",
        "deploy"                                       => "Deploy",
        "enable_git_mode"                              => "Enable Git Mode",
        "enable_on_server_development"                 => "Enable On Server Development",
        "commit_and_push_on_server_changes"            => "Commit and Push On Server Changes",
        "environment_update_pantheon_yml"              => "Environment Update Pantheon YML",
        "check_database_version"                       => "Check Database Version",
        "change_database_version"                      => "Change Database Version",
        "change_object_cache_version"                  => "Change Object Cache Version",
        "change_search_version"                        => "Change Search Version",
        "merge_cloud_development_environment_into_dev" => "Merge Cloud Development Environment Into Dev",
        "migrate_binding_for_site"                     => "Migrate Binding For Site",
        "refresh_cms_modules"                          => "Refresh CMS Modules",
        "remove_site_organization_membership"          => "Remove Site Organization Membership",
        "reset_branch"                                 => "Reset Branch",
        "run_autopilot_job"                            => "Run Autopilot Job",
        "run_command"                                  => "Run Command",
        "update_site_setting"                          => "Update Site Setting",
        "update_site_status"                           => "Update Site Status",
    ];
}
