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
    "title" => "Paketler",
])
    <h5><strong>Depo Adı : </strong><div class="d-inline" id="repoName"></div></h5>
    <h5><strong>Path : </strong><div class="d-inline" id="repoPath"></div></h5>
    <h5><strong>Link : </strong><div class="d-inline" id="repoLink"></div> </h5>
    <div class="mb-4"></div>
    <button type="button" id="gpg_key_export" onclick="gpgKeyExport()" class="btn btn-primary"><i class="fas fa-file-export"></i>  {{__("Gpg Key Export")}}</button><br>
    <small>Not : Bu işlem depoyu client bilgisayarlara eklemek için gereklidir. </small>
    <div class="mb-4"></div>
    <form id="fileUploadForm">
        @include('file-input', [
            'title' => 'Paket Yükle (.deb)',
            'name' => 'file_example',
            'callback' => 'uploadPackage'
        ])<br>
    </form>
    <div id="internalRepoPackagesTable"></div>
@endcomponent