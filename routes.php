<?php

return [
    'index' => 'HomeController@index',
    'install' => 'HomeController@install',
    'load' => 'HomeController@load',
    'install_package' => 'PackageController@install',

    'runTask' => 'TaskController@runTask',
    'checkTask' => 'TaskController@checkTask',

    'get_mirrors' => 'MirrorController@get',
    'get_links_and_paths' => 'MirrorController@getLinksAndPaths',
    'get_address' => 'MirrorController@getAddress',
    'get_sources_list' => 'MirrorController@getSourcesList',
    'add_mirror' => 'MirrorController@add',
    'delete_mirror' => 'MirrorController@delete',
    'edit_mirror' => 'MirrorController@edit',
    'move_mirror' => 'MirrorController@move',
    'add_mirror_address' => 'MirrorController@addAddress',
    'delete_mirror_address' => 'MirrorController@deleteAddress',
    'edit_mirror_address' => 'MirrorController@editAddress',
    'start_mirror' => 'MirrorController@start',
    'stop_mirror' => 'MirrorController@stop',
    'get_size_mirror' => 'MirrorController@getSize',
    'get_disk_info' => 'MirrorController@getDiskInfo',
    'create_mirror_link' => 'MirrorController@createLink',

    'add_cron' => 'CronController@addCron',
    'edit_cron' => 'CronController@editCron',
    'remove_cron' => 'CronController@removeCron',

    'get_log_dates' => 'LogController@getDates',
    'get_log' => 'LogController@get',

    'get_last_mirror_log' => 'LastMirrorLogController@get',

    'get_mirror_names' => 'PackageSearchController@getMirrorNames',
    'get_internal_repo_names' => 'PackageSearchController@getInternalRepoNames',
    'get_mirror_list' => 'PackageSearchController@getMirrorList',
    'get_mirror_search_result' => 'PackageSearchController@getMirrorSearchResult',
    'get_internalRepo_search_result' => 'PackageSearchController@getInternalRepoSearchResult',

    'internal_repo_add' => 'InternalRepoController@add',
    'get_internal_repo' => 'InternalRepoController@get',
    'get_internal_repo_packages' => 'InternalRepoController@getPackages',
    'add_internal_repo_package' => 'InternalRepoController@addPackage',
    'delete_internal_repo_package' => 'InternalRepoController@deletePackage',
    'gpg_key_export' => 'InternalRepoController@gpgKeyExport',
    'delete_internal_repo' => 'InternalRepoController@delete',
    'get_repoDisk_info' => 'InternalRepoController@getRepoDiskInfo',

    'get_uploaded_files' => 'FilesController@getUploadedFiles',
    'upload_file' => 'FilesController@uploadFile',
    'remove_file' => 'FilesController@removeFile',
    'get_filesDisk_info' => 'FilesController@getFilesDiskInfo'
   
];
