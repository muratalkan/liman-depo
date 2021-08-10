<div class="row mb-3" id="{{ $id }}">
    <div class="col-md-3">
        <button type="button" class="btn btn-block btn-dark log_date_pre">{{__("Önceki")}}</button>
    </div>
    <div class="col-md-6">
        <button type="button" class="btn btn-default btn-block log_date_text">{{__("Tarih Seç")}}</button>
        <button type="button" style="display:none;" class="btn btn-default btn-block log_date_all_text">{{__("Tüm Günleri Göster")}}</button>
    </div>
    <div class="col-md-3">
        <button type="button" class="btn btn-block btn-dark log_date_next">{{__("Sonraki")}}</button>
    </div>
</div>
<script src="{{ publicPath("js/date-switch.js") }}"></script>