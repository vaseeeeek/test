$(document).ready(function(){
    $.frequent_questions = {

        init:function(){
            $('.fq_question').redactor();
            $('.fq_answer').redactor();
            
            $('.fq_list').sortable({
                distance: 5,
                helper: 'clone',
                items: 'tbody tr',
                handle: '.sort',
                opacity: 0.75,
                tolerance: 'pointer',
                containment: 'parent',
                stop: function (event, ui) {
                    /*self.showHint();*/
                }
            });
            
            new_qa_txt = $("#new-qa-html").html();
            
            $("#new-qa-html").html("");
            //$("#new-qa-html").remove();
            
            $(".fq_add").click(function () {
                $(".fq_list").append("<tr><td colspan='4'>" + new_qa_txt + "</td></tr>");

                $('.fq-new-question').last().addClass('fq_question');
                $('.fq-new-answer').last().addClass('fq_answer');
                $('.fq-new-question').last().redactor();
                $('.fq-new-answer').last().redactor();

            });

            $("input.fq_submit").click(function () {
                var f = $("#plugins-settings-form");
                
                var fq_questions= $.frequent_questions.fqGetValues(".fq_question", false);
                var fq_answers= $.frequent_questions.fqGetValues(".fq_answer", false);
                var fq_on= $.frequent_questions.fqGetValues(".fq_on", true);
                $.post(f.attr('action'), {
                    questions: fq_questions, 
                    answers: fq_answers,
                    on: fq_on,
                    fq_plugin_enabled: $.frequent_questions.fqGetValue(".fq_plugin_enabled", true),
                    fq_placing: $('[name=fq_placing]:checked').val(),
                    fq_qa_display: $.frequent_questions.fqGetValue(".fq_qa_display", true),
                    fq_target_blank: $.frequent_questions.fqGetValue(".fq_target_blank", true), 
                    fq_link_title: $.frequent_questions.fqGetValue(".fq_link_title",false),
                    fq_page_title: $.frequent_questions.fqGetValue(".fq_page_title",false),
                    fq_expand: $('[name=fq_expand]:checked').val(),
                    fq_question_color: $.frequent_questions.fqGetValue(".fq_question_color", false),
                    fq_answer_color: $.frequent_questions.fqGetValue(".fq_answer_color", false),
            		fq_question_size: $.frequent_questions.fqGetValue(".fq_question_size", false),
            		fq_answer_size: $.frequent_questions.fqGetValue(".fq_answer_size", false),
            		fq_plus_color: $.frequent_questions.fqGetValue(".fq_plus_color", false),
                    fq_minus_color: $.frequent_questions.fqGetValue(".fq_minus_color", false),
            		fq_header_color: $.frequent_questions.fqGetValue(".fq_header_color", false),
            		fq_plus_enabled: $.frequent_questions.fqGetValue(".fq_plus_enabled", true),
                    fq_meta_keywords: $.frequent_questions.fqGetValue(".fq_meta_keywords", false),
                    fq_meta_description: $.frequent_questions.fqGetValue(".fq_meta_description", false),
                    }, function (response) {
                    $("#plugins-settings-form-status").fadeIn('slow', function () {
                       $(this).fadeOut(1000);
                       location.reload();
                    });
                }, "json")
                return false;
            });

            $(".fq_delete").click(function(){
                var fq_id = $(this).attr('id');
                $("tr."+fq_id).remove();
                $(this).remove();
                
            });
            
            $(".fq-edit").click(function(){
                var fq_i = $(this).data('i');
                
                $("div.fq_"+ fq_i).slideToggle('slow', 'linear');
            });
            
            $('.fq_answer_color').spectrum({
                showInput: true,
                preferredFormat: "hex",
                allowEmpty:true});
            $('.fq_question_color').spectrum({
                showInput: true,
                preferredFormat: "hex",
                allowEmpty:true
            });
            $('.fq_plus_color').spectrum({
                showInput: true,
                preferredFormat: "hex",
                allowEmpty:true});
            $('.fq_minus_color').spectrum({
                showInput: true,
                preferredFormat: "hex",
                allowEmpty:true
            });
            $('.fq_header_color').spectrum({
                showInput: true,
                preferredFormat: "hex",
                allowEmpty:true
            });


        },


        




    
        fqGetValues: function (ident, checkbox) {
            var fv = new Array();
                
            jQuery(ident).each(function(){
                if (checkbox) {
                    if (jQuery(this).attr('checked')) {
                        fv.push(1);
                    } else {
                        fv.push(0);
                    }
                    
                    
                } else {
                    fv.push(jQuery(this).val());
                }
                
            });
            return fv;
        },

        fqGetValue: function(ident, checkbox) {
            var fv;
                
                if (checkbox) {
                    if (jQuery(ident).attr('checked')) {
                        fv = 1;
                    } else {
                        fv = 0;
                    }
                    
                    
                } else {
                    fv = jQuery(ident).val();
                }
                

            return fv;
        },





    }
    $.frequent_questions.init();
});