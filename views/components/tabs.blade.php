<ul class="nav nav-tabs mb-2" role="tablist">
    @foreach ($tabs as $tab => $options)
        @if(!isset($options['subTabs']))
            <li class="nav-item">
                <a class="nav-link @if ($loop->first) active @endif" onclick="loadPageContent('{{ $options['view'] }}', '{{ $tab }}', '{{ $options['onclick'] }}', {{ $options['notReload'] ? 'true' : 'false' }})" href="#{{ $tab }}" data-toggle="tab"><i class="{{ $options['icon'] }} @if($options['title']) mr-2 @endif"></i>{{ $options['title'] }}</a>
            </li>
        @else
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                    <i class="{{ $options['icon'] }} @if($options['title']) mr-2 @endif"></i>{{ $options['title'] }}<span class="caret"></span>
                </a>
                <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 40px, 0px);">
                    @foreach ($options['subTabs'] as $subTab => $subOptions)
                        <a class="dropdown-item" href="#{{ $subTab }}" onclick="loadPageContent('{{ $subOptions['view'] }}', '{{ $subTab }}', '{{ $subOptions['onclick'] }}', {{ $subOptions['notReload'] ? 'true' : 'false' }})" data-toggle="tab">
                            <i class="{{ $subOptions['icon'] }} @if($subOptions['title']) mr-3 @endif" style="width: 12%;"></i>{{ $subOptions['title'] }}
                        </a>
                    @endforeach
                </div>
            </li>
        @endif
    @endforeach
</ul>

<div class="tab-content" style="min-height: 50px;">
    @foreach ($tabs as $tab => $options)
        @if(!isset($options['subTabs']))
            <div id="{{ $tab }}" class="tab-pane @if ($loop->first) active @endif"></div>
        @else
            @foreach ($options['subTabs'] as $subTab => $subOptions)
                <div id="{{ $subTab }}" class="tab-pane"></div>
            @endforeach
        @endif
    @endforeach
</div>


@php
    $tabsCollection = collect($tabs);
    $firstTab = $tabsCollection->keys()->first();
    $firstOptions = $tabsCollection->first();
@endphp

<script>

    if(location.hash === ""){
        loadPageContent('{{ $firstOptions['view'] }}', '{{ $firstTab }}', '{{ $firstOptions['onclick'] }}')
    }      

    function loadPageContent(view, tab, onclick, notReload=false){
        if(notReload && $('.tab-content').find('#'+tab).html().length){
            eval(onclick);
            return;
        }
        showSwal('{{__("Yükleniyor...")}}','info');
        $('.tab-content').find('#'+tab).html("");
        let data = new FormData();
        data.append("view", view);
        request("{{API("load")}}", data, function(response){
            $('.tab-content').find('#'+tab).html(response);
            Swal.close();
            eval(onclick);
        }, function(response){
            let error = JSON.parse(response)["message"];
            showSwal(error, 'error', 2000);
        });
    }
</script>