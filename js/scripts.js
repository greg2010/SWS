function SendRequest(){
    $.ajax({
        type: "POST",
        url: "getChars.php",
        data: "sid=<?=session_id()?>&keyID="+$('#keyID').val()+"&vCode="+$('#vCode').val(),
        datatype: 'json',
        success: function(json){
            if (json.status !== 0) {
                $('#chars').empty();
                $('#chars').attr('hidden', 1);
                $('div[role="alert-api"]').empty();
                $('div[role="alert-api"]').removeAttr('hidden').text("API server has responed with an error: "+json.message);
            } else {
                $('div[role="alert-api"]').empty();
                $('div[role="alert-api"]').attr('hidden', 1);
                $('#charList').empty();
                $('#chars').removeAttr('hidden');
                $.each(json, function(i, chars) {
                    if (typeof(chars) === "object") {
                        $('#charList').append($('<input>').attr('type', 'radio').attr('value', chars.characterName).attr('class', 'r_button').attr('name', 'login'));
                        if (chars.valid === 1) {
                            var id = "r_1";
                            var className = "glyphicon glyphicon-ok";
                        } else {
                            var id = "r_2";
                            var className = "glyphicon glyphicon-remove";
                        }
                        $(':radio[value="'+chars.characterName+'"]').attr('id', id).after(function() {
                            var label = $("<label>");
                            $(label).attr('for', id).text(chars.characterName).append(function() {
                                var span = $("<span>");
                                $(span).attr('class', className);
                                return $(span);
                            });
                            return $(label)
                        });
                    }
                });
                $('#go').removeAttr('disabled')
            }
        }
    });
}
$(document).ready(function() {
    $('#email').blur(function() {
        if($(this).val() !== '') {
            var pattern = /^([a-z0-9_\.-])+@[a-z0-9-]+\.([a-z]{2,4}\.)?[a-z]{2,4}$/i;
            if(pattern.test($(this).val())){
                $(this).css({'border' : '1px solid #569b44'});
                $('div[role="alert-email"]').attr('hidden', 1);
            } else {
                $(this).css({'border' : '1px solid #ff0000'});
                $('div[role="alert-email"]').removeAttr('hidden').text('Your e-mail is not valid!');
            }
        } else {
            // Поле email пустое, выводим предупреждающее сообщение
            $(this).css({'border' : ''});
            $('div[role="alert-email"]').attr('hidden', 1);
        }
    });
    var passValid;
    var validColor = "#569b44";
    var validBorder = "1px solid #569b44";
    var invalidColor = "#ff0000";
    var invalidBorder = "1px solid #ff0000";
        $('#password').on("input", (function() {
        if($(this).val() !== '') {
            var pwd = $(this).val();
            var count = pwd.length;
            var numberValid;
            var lowerValid;
            var upperValid;
            if(count > 7){
                $('#length').css({'color' : validColor});
                if(!/\d/.test(pwd)){
                    $('#numbers').css({'color' : invalidColor});
                     //$('div[role="alert-password"]').removeAttr('hidden').text('Your password has to contain at least 1 digit!');
                    passValid = 0;
                    numberValid = 0;
                    console.log("numbers", numberValid);
                } else {
                    $('#numbers').css({'color' : validColor});
                    //$('div[role="alert-password"]').val().replace('Your password has to contain at least 1 digit!','');
                    passValid = 0;
                    numberValid = 1;
                    console.log("numbers", numberValid);
                }
                if(!/[a-z]/.test(pwd)){
                    $('#lowerCase').css({'color' : invalidColor});
                    // $('div[role="alert-password"]').removeAttr('hidden').text('Your password has to contain at least 1 lower-case!');
                    passValid = 0;
                    lowerValid = 0;
                    console.log("lower", lowerValid);
                } else {
                    $('#lowerCase').css({'color' : validColor});
                    //$('div[role="alert-password"]').val().replace('Your password has to contain at least 1 lower-case!','');
                    passValid = 0;
                    lowerValid = 1;
                    console.log("lower", lowerValid);
                }
                if(!/[A-Z]/.test(pwd)){
                    $('#upperCase').css({'color' : invalidColor});
                     //$('div[role="alert-password"]').removeAttr('hidden').text('Your password has to contain at least 1 upper-case!');
                    passValid = 0;
                     upperValid = 0;
                     console.log("upper", upperValid);
                } else {
                    $('#upperCase').css({'color' : validColor});
                    //$('div[role="alert-password"]').val().replace('Your password has to contain at least 1 upper-case!','');
                    passValid = 0;
                    upperValid = 1;
                    console.log("upper", upperValid);
                }
                if (numberValid === 1 && lowerValid === 1 && upperValid === 1) {
                    passValid = 1;
                    $(this).css({'border' : validBorder});
                    $('div[role="alert-password"]').attr('hidden', 1);
                }
            } else {
                passValid = 0;
                $(this).css({'border' : invalidBorder});
                $('#length').css({'color' : invalidColor});
                //$('div[role="alert-password"]').removeAttr('hidden').text('Your password must consist minimum of 8 symbols!');
            }
        } else {
            $('#reqs').css({'color' : invalidColor});
            passValid = 0;
            // Поле pwd пустое, выводим предупреждающее сообщение
            $(this).css({'border' : invalidBorder});
            $('div[role="alert-password"]').attr('hidden', 1);
        }
    }));
    $('#password-repeat').blur(function() {
        if (passValid === 1) {
            if($(this).val() !== $('#password').val()) {
                $(this).css({'border' : invalidBorder});  
                $('div[role="alert-password-repeat"]').removeAttr('hidden').text('Passwords don\'t match!');
            } else {
                $(this).css({'border' : validBorder});
                $('div[role="alert-password-repeat"]').attr('hidden', 1).replace('Passwords don\'t match!\n', '');
            }
        }
    });
});