if(!logDatesInfo){
    var logDatesInfo = [];
}

function initLogDates(id, data, nextCallback=null) {
    setLogDates(id, {
        index: data.length - 1,
        dates: data,
        next: nextCallback
    });
    $('#'+id).find('.log_date_pre').off('click');
    $('#'+id).find('.log_date_pre').click(function(){
        pre(id);
    });
    $('#'+id).find('.log_date_next').off('click');
    $('#'+id).find('.log_date_next').click(function(){
        next(id);
    });
    $('#'+id).find('.log_date_all_text').off('click');
    $('#'+id).find('.log_date_all_text').click(function(){
        last(id);
    });
}

function getCurrentDate(id) {
    return logDatesInfo[id].dates[logDatesInfo[id].index];
}

function setLogDates(id, data) {
    $('#mirrorLogs').find('.overlay').show();
    logDatesInfo[id] = data;
    $('#'+id).find('.log_date_text').text(logDatesInfo[id].dates[logDatesInfo[id].index]);
    logDatesInfo[id].next && logDatesInfo[id].next();
    if(logDatesInfo[id].index <= 0){
        $('#'+id).find('.log_date_pre').attr("disabled", true);
    }else{
        $('#'+id).find('.log_date_pre').attr("disabled", false);
    }
    if(logDatesInfo[id].index === logDatesInfo[id].dates.length - 1){
        $('#'+id).find('.log_date_next').attr("disabled", true);
    }else{
        $('#'+id).find('.log_date_next').attr("disabled", false);
    }
    $('#'+id).find('.log_date_text').datepicker('destroy');
    $('#'+id).find('.log_date_text').datepicker({
        autoclose: true,
        zIndexOffset: 9999,
        format: 'yyyy-m-d',
        beforeShowDay: function(date)
        {
            if ($.inArray(date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate(), logDatesInfo[id].dates) !== -1)
            {
                return;
            }
            return false;
        }
    })
    .off('changeDate')
    .on('changeDate', function(ev){
        let data = logDatesInfo[id];
        data.index = $.inArray(ev.format(), logDatesInfo[id].dates);
        setLogDates(id, data);
        $('#'+id).find('.log_date_text').datepicker('hide');
    });
}

function pre(id) {
    let data = logDatesInfo[id];
    data.index = data.index - 1;
    setLogDates(id, data);
}

function next(id) {
    let data = logDatesInfo[id];
    data.index = data.index + 1;
    setLogDates(id, data);
}

function first(id) {
    let data = logDatesInfo[id];
    data.index = 0;
    setLogDates(id, data);
}

function last(id) {
    let data = logDatesInfo[id];
    data.index = data.dates.length - 1;
    setLogDates(id, data);
}