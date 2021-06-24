@include('modal',[
    "id"=>"addMirror",
    "title" => "Aynalama Ekle",
    "url" => API('add_mirror'),
    "next" => "getMirrors",
    "inputs" => [
            "Aynalama Adı" => "mirrorName:text:Aynalamanın Adı",
            "Aynalama Tanımı" => "description:text:Aynalamanın Tanımı",
            "İndirme Dizini" => "path:text:Aynalama Dosyaların Bulunacağı Dizin"
        ],
    "submit_text" => "Ekle"
])

@include('modal',[
    "id"=>"editMirror",
    "title" => "Aynalama Düzenle",
    "url" => API('edit_mirror'),
    "next" => "getMirrors",
    "inputs" => [
            "Aynalama Adı" => "name:text:Aynalamanın Adı",
            "Aynalama Tanımı" => "description:text:Aynalamanın Tanımı",
            "Aynalama Ayarlarını Düzenle" => "editMirrorConfig:checkbox",
            "set nthreads" => "set_nthreads:text:set nthreads",
            "set _tilde" => "set_tilde:text:set _tilde",
            "storagePath:storagePath" => "storagePath:hidden",
            "oldName:oldName" => "oldName:hidden",
            "oldDescription:oldDescription" => "oldDescription:hidden",
            "old_set_nthreads:old_set_nthreads" => "old_set_nthreads:hidden",
            "old_set_tilde:old_set_tilde" => "old_set_tilde:hidden"
        ],
    "submit_text" => "Değişiklikleri Uygula"
])


@component('modal-component',[
    "id" => "linksAndPathsComponent",
    "title" => "Sembolik Linkler ve Dosya Yolları"
])
    <button type="button" class="btn btn btn-primary mb-2" data-toggle="modal" onclick="getLinksAndPaths()">
        <i class="fas fa-sync mr-1"></i> Yenile
    </button>
    <button type="button" class="btn btn btn-primary mb-2" data-toggle="modal" onclick="getSizeMirror()">
        <i class="fas fa-calculator mr-1"></i> Boyut Hesapla
    </button>
    <button type="button" class="btn btn btn-primary mb-2" data-toggle="modal" onclick="getSourcesList()">
        <i class="fas fa-list-ul mr-1"></i> Sources List
    </button>

    <div id="linkPathDiv">
        <div id="linkPathTable"></div>
        <div class="overlay">
            <div class="spinner-border" role="status">
                <span class="sr-only">{{__('Loading')}}...</span>
            </div>
        </div>
    </div>
    
@endcomponent

@component('modal-component',[
    "id" => "sourcesListModal",
    "title" => "Sources List"
])
    <div class="alert alert-info" role="alert">
        {{__("Depoları sisteminize eklemek için aşağıdaki satırları 'sources.list.d' dosyasına eklemelisiniz.")}}
    </div>
    <div style="max-height: 600px; overflow-y: auto;">
        <ul class="list-group">
        </ul>
    </div>
@endcomponent


@component('modal-component',[
    "id" => "addressComponent",
    "title" => "Adresler",
])
    @include('modal-button',[
        "class"     =>  "btn btn-primary mb-2",
        "target_id" =>  "addMirrorAddress",
        "text"      =>  "Adres Ekle",
        "icon" => "fas fa-plus mr-1"
    ])
    <div id="addressTable"></div>
@endcomponent


@component('modal-component',[
    "id" => "addMirrorAddress",
    "title" => "Adres Ekle",
    "footer" => [
        "class" => "btn-success",
        "onclick" => "addMirrorAddress()",
        "text" => "Ekle"
    ],
])
    @include('inputs',[
        "inputs" => [
            "Durum:activeState" => [
                "Aktif" => "true",
                "Pasif" => "false"
            ],
            "Depo Adresi" => "address:text:deb http://depo.pardus.org.tr/pardus ondokuz main",
            "Sembolik Link Adı" => "link:text:pardus",
        ]
    ])
@endcomponent


@component('modal-component',[
    "id" => "editAddressComponent",
    "title" => "Adres Düzenle",
    "footer" => [
        "class" => "btn-success",
        "onclick" => "editMirrorAddress()",
        "text" => "Değişiklikleri Uygula"
    ],
])
    @include('inputs',[
        "inputs" => [
            "Durum:activeState" => [
                "Aktif" => "true",
                "Pasif" => "false"
            ],
            "Depo Adresi" => "address:text:Bu kısımda sadece versiyon, kod adı ve paketler değiştirilebilir.",
            "Sembolik Link Adı" => "link:text:Sembolik depo link adı",
            "oldLinkName:oldLinkName" => "oldLink:hidden",
            "oldAddress:oldAddress" => "oldAddress:hidden",
            "oldActiveState:oldActiveState"  => "oldActiveState:hidden",
            "mirrorName:mirrorName" => "mirrorName:hidden",
            "storagePath:storagePath" => "storagePath:hidden"
        ]
    ])
@endcomponent


@component('modal-component',[
    "id" => "addCronComponent",
    "title" => "Görev Tanımla",
    "footer" => [
        "class" => "btn-success",
        "onclick" => "addCron()",
        "text" => "Tanımla"
    ],
])

    @include('inputs',[
        "inputs" => [
            "Ekleme Seçeneği:cronOption" => [
                "Arayüz"=>"arayüz",
                "Özel"=>"custom"
            ],
        ],
    ])
    <div class="row" id="cronJob">
        <div class="col-2">
            @php $minutesArray = \App\Controllers\CronController::getMinutes(); @endphp
            <div class="form-group">
                <label>Dakikalar</label>
                <select class="form-control select2" name="cronMinutes">
                    @foreach($minutesArray as $key => $value)
                        <option value="{{$key}}">{{$value}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-2">
            @php $hoursArray = \App\Controllers\CronController::getHours(); @endphp
            <div class="form-group">
                <label>Saatler</label>
                <select class="form-control select2" name="cronHours">
                    @foreach($hoursArray as $key => $value)
                        <option value="{{$key}}">{{$value}}</option>
                    @endforeach
                </select>
            </div>

        </div>
        <div class="col-2">
            @php $daysArray = \App\Controllers\CronController::getDays(); @endphp
            <div class="form-group">
                <label>Günler</label>
                <select class="form-control select2" name="cronDays">
                    @foreach($daysArray as $key => $value)
                        <option value="{{$key}}">{{$value}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-3">
            @php $monthsArray = \App\Controllers\CronController::getMonths(); @endphp
            <div class="form-group">
                <label>Ay</label>
                <select class="form-control select2" name="cronMonths">
                    @foreach($monthsArray as $key => $value)
                        <option value="{{$key}}">{{$value}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-3">
            @php $weekDaysArray = \App\Controllers\CronController::getWeekDays(); @endphp
            <div class="form-group">
                <label>Hafta Günleri</label>
                <select class="form-control select2" name="cronWeekDays">
                    @foreach($weekDaysArray as $key => $value)
                        <option value="{{$key}}">{{$value}}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="form-group text_input" style="display:none;">
        <label>{{ __('Cron Girdisi') }}</label>
        <input type="text" name="custom" class="form-control" placeholder="* * * * * = Dakika Saat Gün Ay Yıl">
        <small>* * * * * = Dakika Saat Gün Ay Yıl</small>
    </div>
@endcomponent

{{-- editCron modal Başlangıç --}}
<div class="modal fade" id="editCronComponent" aria-modal="true" style="display: none;">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Cron Düzenle</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <label>Cron</label>
            <input type="text" name="time" placeholder="* * * * * = Dakika Saat Gün Ay Yıl" class="form-control " required="">                                                    <small class="form-text text-muted">* * * * * = Dakika Saat Gün Ay Yıl</small>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" onclick="removeCron()">Cron Kaldır</button>
        <button class="btn btn-success" onclick="editCron()">Güncelle</button>
      </div>
    </div>
  </div>
</div> 
{{-- editCron modal Son --}}


@component('modal-component',[
    "id" => "taskModal",
    "title" => "Görev İşleniyor",
])@endcomponent