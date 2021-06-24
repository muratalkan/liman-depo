<div class="input-group" style="max-width: 260px; float:right;">
    <span class="input-group-btn" style="padding-right:10px;">
        <button @if($current != 1) onclick="{{$onclick . '(' . ($current - 1 ). ')'}}" @else disabled @endif class="btn btn-default" type="button">{{__("Önceki")}}</button>
    </span>
    <select onchange="{{$onclick . '(this.value)'}}" class="form-control">
        @for($i = 1 ; $i <= intval($count); $i++)
            <option value="{{$i}}"@if($i == $current) selected @endif">{{$i}}</option>
        @endfor
    </select>
    <span class="input-group-btn" style="padding-left:10px;">
        <button @if($current != $count) onclick="{{$onclick . '(' . ($current + 1 ). ')'}}" @else disabled @endif class="btn btn-default" type="button">{{__("Sonraki")}}</button>
    </span>
</div>