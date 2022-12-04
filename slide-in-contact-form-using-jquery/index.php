<html>
<head>
<link type="text/css" href="style.css" rel="stylesheet" />
<script src="jquery-3.2.1.min.js" type="text/javascript"></script>
<script type="text/javascript">
            $(document).ready(function(){

                	$( "#btn-contact" ).on( "click", function() {
                        
                    if ($("#frm-contact").is(":hidden"))
                    {
                        $("#frm-contact").slideDown("slow");
                    }
                    else
                    {
                        $("#frm-contact").slideUp("slow");
                    }
                });
                
            });

            function sendContact() {
                	var valid;	
                	valid = validateContact();
                	if(valid) {
                		jQuery.ajax({
                		url: "contact_mail.php",
                		data:'userName='+$("#userName").val()+'&userEmail='+$("#userEmail").val()+'&subject='+$("#subject").val()+'&content='+$(content).val(),
                		type: "POST",
                		success:function(data){
                		$("#mail-status").html(data);
                        setTimeout(ajaxCallback, 2000);
                		},
                		error:function (){}
                		});
                	}
            }

            function ajaxCallback() {
            	
                    var btnHTML = '<button name="submit" class="btnAction" onclick="sendContact();">Send</button';
                    $("#mail-status").html(btnHTML);
                    $("#frm-contact").slideUp("slow");
            }

            function validateContact() {
            	var valid = true;	
            	$(".demoInputBox").css('background-color','');
            	
            	if(!$("#userName").val()) {
            		$("#userName").css('background-color','#f7dddd');
            		valid = false;
            	}
            	if(!$("#userEmail").val()) {
            		$("#userEmail").css('background-color','#f7dddd');
            		valid = false;
            	}
            	if(!$("#userEmail").val().match(/^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/)) {
            		$("#userEmail").css('background-color','#f7dddd');
            		valid = false;
            	}
            	if(!$("#subject").val()) {
            		$("#subject").css('background-color','#f7dddd');
            		valid = false;
            	}
            	if(!$("#content").val()) {
            		$("#content").css('background-color','#f7dddd');
            		valid = false;
            	}
            	
            	return valid;
            }
        </script>
</head>
<body>
    <div class="body-content">
        <div id="form-outer">
            <div id="frm-contact">
                <div>
                    <input type="text" name="userName" id="userName"
                        class="demoInputBox" PlaceHolder="Name">
                </div>
                <div>
                    <input type="text" name="userEmail" id="userEmail"
                        class="demoInputBox" PlaceHolder="Email">
                </div>
                <div>
                    <input type="text" name="subject" id="subject"
                        class="demoInputBox" PlaceHolder="Subject">
                </div>
                <div>
                    <textarea name="content" id="content"
                        class="demoInputBox" rows="3"
                        PlaceHolder="Content"></textarea>
                </div>
                <div id="mail-status">
                    <button name="submit" class="btnAction"
                        onclick="sendContact();">Send</button>
                </div>
            </div>
            <div id="btn-contact">Contact Me</div>
        </div>

        <div class="txt-content">
            <p>Mauris blandit orci id risus tristique, non mattis ante
                finibus. Duis volutpat tempor magna non posuere. Mauris
                a vestibulum ligula, id commodo metus. Proin hendrerit,
                enim at ullamcorper mattis, libero nisi blandit nulla,
                eu porta tortor orci vel ipsum. Curabitur ullamcorper
                imperdiet lorem nec pretium. Morbi finibus, mauris vitae
                feugiat euismod, purus magna blandit nunc, ac tincidunt
                mi lacus eget leo.</p>
        </div>
    </div>
</body>
</html>
