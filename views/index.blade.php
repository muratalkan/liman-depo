<?php
$checkPackage = \App\Controllers\PackageController::verifyInstallation();
if (!$checkPackage) {
    echo "<script>window.location.href = '".navigate('install')."';</script>";
}
?>

@include('components.tabs', [
    "tabs" => [
        "mirrorList" => [
            "title" => __('Aynalama'),
            "icon" => "fas fa-cloud-download-alt mr-2",
            "view" => "mirror_list",
            "notReload" => true,
            "subTabs" => [
                "mirrorList" => [
                    "title" => __('Aynalama Listesi'),
                    "icon" => "fas fa-list mr-2",
                    "view" => "mirror_list",
                    "onclick" => "getMirrors()",
                    "notReload" => true,
                ],
                "log" => [
                    "title" => __('Loglar'),
                    "icon" => "fas fa-history mr-2",
                    "view" => "log",
                    "onclick" => "getLogDates()",
                    "notReload" => true,
                ],
                "lastMirrorLog" => [
                    "title" => __('Son Çalıştırma Logu'),
                    "icon" => "fas fa-file-alt mr-2",
                    "view" => "last_mirror_log",
                    "onclick" => "getLastMirrorLog()",
                    "notReload" => true,
                ]
            ]
        ],
        "internalRepo" => [
            "title" => __('Yerel Depo'),
            "icon" => "fas fa-box mr-2",
            "view" => "internal_repo",
            "onclick" => "getInternalRepo()",
            "notReload" => true,
        ],
        "packageSearch" => [
            "title" => __('Paket Ara'),
            "icon" => "fas fa-search mr-2",
            "view" => "package_search",
            "onclick" => "initializePackageSearch()",
            "notReload" => true,
        ],

    ]
])

<script>
    $("a[href$='#mirrorList']").click();
</script>