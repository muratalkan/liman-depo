<script>

    $('.modal').on('hidden.bs.modal', function(e){
        if (typeof $(this).find('form')[0] !== 'undefined'){
            $(this).find(".alert").fadeOut();
            $(this).find('form')[0].reset();
        }
    });

    function resetModalForm(modal){
        $(modal).on('hidden.bs.modal', function(e){
            $(this).find('input').val("");
        });
    }

    function setAttr_Required(modal, nameField, bool){
        $(modal).find(`input[name='${nameField}']`).attr("required", bool);
    }

    function setAttr_Readonly(modal, nameField, bool){
        $(modal).find(`input[name='${nameField}']`).prop("readonly", bool);
    }

    function setInputValue(modal, input, value){
        $(modal).find('input[name="'+input+'"]').val(value)
    }

</script>