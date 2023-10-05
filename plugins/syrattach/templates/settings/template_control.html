<div style="text-align: right;padding: 0.3em 0">
    <a href="javascript:void(0);" class="inline-link" id="{$settings.id}-restore-template"><i class="icon16 delete"></i> {_wp('Restore original template')}</a>
</div>
<div class="s-editor-core-wrapper">
    <textarea id="{$settings.id}" name="syrattach_template">{$template}</textarea>
</div>
<div style="padding: 0.5em;margin: 0.5em 0; border:1px solid #e9e9e9; line-height: 120%">
    {_wp('Variables available within the template:')}<br>
    <code style="padding: 2px 4px;font-size: 90%; background-color: #f9f2f4;color:#c7254e"><b>$attachments</b></code> — {_wp('A list of file data. Each file item is an array with keys:')}
    <ul style="list-style-type: none">
        <li><code style="padding: 2px 4px;font-size: 90%; background-color: #f9f2f4;color:#c7254e">id</code> — {_wp('Attachment ID#')}</li>
        <li><code style="padding: 2px 4px;font-size: 90%; background-color: #f9f2f4;color:#c7254e">name</code> — {_wp('File name')}</li>
        <li><code style="padding: 2px 4px;font-size: 90%; background-color: #f9f2f4;color:#c7254e">ext</code> — {_wp('File extension')}</li>
        <li><code style="padding: 2px 4px;font-size: 90%; background-color: #f9f2f4;color:#c7254e">description</code> — {_wp('File description')}</li>
        <li><code style="padding: 2px 4px;font-size: 90%; background-color: #f9f2f4;color:#c7254e">size</code> — {_wp('File size in bytes')}</li>
        <li><code style="padding: 2px 4px;font-size: 90%; background-color: #f9f2f4;color:#c7254e">url</code> — {_wp('File URL')}</li>
    </ul>
    
</div>
<script type="text/javascript">
    $(function(){
        var c = CodeMirror.fromTextArea(document.getElementById('{$settings.id}'),{
            mode: "text/html",
            tabMode: "indent",
            height: "dynamic",
            lineWrapping: true
        });
        $("#{$settings.id}").change(function(){
            c.setValue($(this).val());
        });
        
        {if !$template_modified}
            $("#{$settings.id}-restore-template").hide();
        {/if}
        
        $("#{$settings.id}").closest("#plugins-settings-form").submit(function(){
            $("#{$settings.id}-restore-template").show();
        });
        
        $("#{$settings.id}-restore-template").click(function(){
            if(confirm("{_wp("Do you really want to reset the template to the original one? All changes you're made will lost!")|escape:javascript}") === true) {
                $.get("?plugin=syrattach&module=settings&action=originaltemplate", function(r){
                    if(r.status == 'ok'){
                        $("#{$settings.id}").val(r.data.template);
                        c.setValue($("#{$settings.id}").val());
                        $("#{$settings.id}-restore-template").hide();
                    }
                });
            }
            return false;
        });
    });
</script>