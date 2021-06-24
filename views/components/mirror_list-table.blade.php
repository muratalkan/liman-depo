@if(isset($mirrorArray))
	@include('table', [
			'value' => $mirrorArray,
			'title' => [
				'İsim', 'Tanım', 'Görev', 'Son Çalıştırma', 'Durum', 'İşlem', "*hidden*", "*hidden*", "*hidden*", "*hidden*", "*hidden*", "*hidden*", "*hidden*", "*hidden*"
			],
			'display' => [
				'name', 'description', 'cron', 'lastRun', 'status', 'operation', 'storagePath:storagePath', 'oldName:oldName', 'oldDescription:oldDescription', 'oldStoragePath:oldStoragePath', 'set_nthreads:set_nthreads', 'set_tilde:set_tilde', 'old_set_nthreads:old_set_nthreads', 'old_set_tilde:old_set_tilde'
			],
			'menu' => [
				'Görüntüle' => [
					'target' => 'getLinksAndPaths',
					'icon' => 'fa-eye'
				],
				'Adresler' => [
					'target' => 'getAddress',
					'icon' => 'fa-book'
				],
				'Cron' => [
					'target' => 'openAddCron',
					'icon' => 'fa-clock'
				],
				'Disk Bilgisi' => [
					'target' => 'getDiskInfo',
					'icon' => 'fa-hdd'
				],
				'Dizini Güncelle' => [
					'target' => 'moveMirror',
					'icon' => 'fa-folder-open'
				],
				'Düzenle' => [
					'target' => 'editMirror',
					'icon' => 'fa-edit'
				],
				'Sil' => [
					'target' => 'deleteMirror',
					'icon' => 'fa-trash'
				]
			]
		])
@endif

@if(isset($linkAndPathArr))
	@include('table', [
			'value' => $linkAndPathArr,
			'title' => [
				'Sembolik Link', 'İndirme Dizini', 'Boyut', "*hidden*", "*hidden*", "*hidden*", "*hidden*", "*hidden*", "*hidden*", "*hidden*"
			],
			'display' => [
				'link', 'downloadPath', 'downloadSize', 'name:name', 'storagePath:storagePath', 'extUrl:extUrl', 'extRepoName:extRepoName', 'oldLinkName:oldLinkName', 'checkLink:checkLink', 'checkDownload:checkDownload'
			],
			"onclick" =>  "openLinkAddress",
			'menu' => [
				'Yeni Sembolik Link Oluştur' => [
					'target' => 'createSymbolicLink',
					'icon' => 'fa-link'
				]
			]
		])
@endif

@if(isset($mirrorAddressArr))
	@include('table', [
			'value' => $mirrorAddressArr,
			'title' => ['Durum', 'Adres', 'Sembolik Link Adı', '*hidden*', '*hidden*', '*hidden*', '*hidden*', '*hidden*', '*hidden*', '*hidden*'],
			'display' => [
				'activeStateTxt', 'address', 'linkTxt', 'link:link', 'oldLink:oldLink', 'activeState:activeState', 'oldActiveState:oldActiveState',  'oldAddress:oldAddress', 'storagePath:storagePath', 'mirrorName:mirrorName'
			],
			'menu' => [
				'Düzenle' => [
					'target' => 'editAddressComponent',
					'icon' => 'fas fa-edit'
				],
				'Sil' => [
					'target' => 'deleteAddress',
					'icon' => 'fas fa-trash'
				]
			]
		])
@endif