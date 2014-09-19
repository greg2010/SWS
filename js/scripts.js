function SendRequest(){
    $.ajax({
        type: "POST",
        url: "getChars.php",
        data: "sid=<?=session_id()?>&keyID="+$('#keyID').val()+"&vCode="+$('#vCode').val(),
        datatype: 'json',
        success: function( json ) {
            $.each(json, function(i, value) {
                $('#myselect').append($('<option>').text(value).attr('value', value));
            });
        }
    });
    }
//    document.getElementById("submit").disabled = false;
//    };
//$(document).ready(function() {
//    $('#email').blur(function() {
//        if($(this).val() !== '') {
//            var pattern = /^([a-z0-9_\.-])+@[a-z0-9-]+\.([a-z]{2,4}\.)?[a-z]{2,4}$/i;
//            if(pattern.test($(this).val())){
//                $(this).css({'border' : '1px solid #569b44'});
//                $('#email-valid').text('');
//            } else {
//                $(this).css({'border' : '1px solid #ff0000'});
//                $('#email-valid').text('Your e-mail is not valid!').css({'color' : '#ff0000'});
//            }
//        } else {
//            // Поле email пустое, выводим предупреждающее сообщение
//            $(this).css({'border' : '1px solid #ff0000'});
//            $('#email-valid').text('Please type your e-mail!').css({'color' : '#ff0000'});
//        }
//    });
//        $('#password').blur(function() {
//        if($(this).val() !== '') {
//            var count = $(this).val().length;
//            if(count > 6){
//                $(this).css({'border' : '1px solid #569b44'});
//                $('#password-valid').text('');
//            } else {
//                $(this).css({'border' : '1px solid #ff0000'});
//                $('#password-valid').text('Your password must consist minimum of 6 symbols!').css({'color' : '#ff0000'});
//            }
//        } else {
//            // Поле email пустое, выводим предупреждающее сообщение
//            $(this).css({'border' : '1px solid #ff0000'});
//            $('#password-valid').text('Password field is empty!').css({'color' : '#ff0000'});
//        }
//    });
//});