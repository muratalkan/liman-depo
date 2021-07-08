@include('modal',[
    "id"=>"internalRepoAddModal",
    "title" =>"Yerel Depo Ekle",
    "url" => API("internal_repo_add"),
    "next" => "getInternalRepo",
    "inputs" => [
        "Depo Adı" => "repo_name:text:Depo Adını Giriniz",
        "Depo Dizini" => "repo_path:text:Deponun bulunacağı dizin",
        "Kod Adı" => "repo_codename:text:Deponun yapısını belirlemek için kullanılır - debian,trusty",
        "Mimariler" => "repo_architectures:text:Depoda bulunacak mimariler - amd64,i386",
        "Bileşenler" => "repo_components:text:Paketlerin alınabileceği bileşenler - main non-free contrib",
        "Tanım" => "repo_description:text:Deponun açıklaması",
    ],
    "submit_text" => "Ekle"
])

@component('modal-component',[
    "id" => "internalRepoPackagesComponent",
    "title" => "Yerel Paketler",
])


<div class="col">
    <div class="card border card-primary card-outline">
            <div class="card-header" style="background-color:#0275d8;">
            <h3 class="card-title text-light" id="repoName"></h3>
        </div>
        <div class="card-body">
            <p class="card-text" id="repoPath"></p>
            <p class="card-text" id="repoSourceList"></p>
            <button style="background-color:#0275d8;" type="button" class="btn btn-primary" onclick="openLocalRepoURL()">
                <i class="fas fa-link mr-2"></i>{{__("Depo Adresi")}}
            </button>
            <button style="background-color:#0275d8;" type="button" data-toggle="tooltip" title="{{ __('Depoyu istemciye eklemek için GPG anahtarı gereklidir') }}" onclick="gpgKeyExport()" class="btn btn-primary">
                <i class="fas fa-file-export mr-2"></i>{{__("GPG Anahtarını Dışa Aktar")}}
            </button>
        </div>
    </div>
</div>

    <form id="fileUploadForm">
        @include('components.file-input', [
            'title' => 'Paket Yükle (.deb)',
            'name' => 'file_example',
            'callback' => 'uploadPackage'
        ])
    </form>
    <div id="internalRepoPackagesTable"></div>
@endcomponent


@component('modal-component',[
    "id" => "uploadedFilesModal",
    "title" => "Dosyalar"
])
    @include('modal-button',[
        "class"     =>  "btn btn-primary mb-2",
        "target_id" =>  "uploadFileModal",
        "text"      =>  "Dosya Ekle",
        "icon" => "fas fa-folder-plus mr-1"
    ])

    <button type="button" class="btn btn btn-primary mb-2" onclick="openFilesURL()"><i class="fas fa-link mr-2"></i>{{__("Dosya Adresi")}}</button>
    <button type="button" class="btn btn btn-primary mb-2" onclick="getFilesDiskInfo()"><i class="fas fa-hdd mr-2"></i>{{__("Disk Bilgisi")}}</button>

    <div id="uploadedFilesDiv">
        <div id="uploadedFilesTable"></div>
    </div>
    
@endcomponent

@component('modal-component',[
        "id"=>"uploadFileModal",
        "title" => "Dosya Yükle"
    ])
  
        @include('components.file-input', [
            'title' => 'Dosya Yükle',
            'name' => 'file_example',
            'callback' => 'uploadFile'
        ])

        <small>{{__("Yükleme konumu")}}: /var/www/html/Files2Share</small>

@endcomponent

@include('components.functions')