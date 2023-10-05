$(function (){
	if($("#product-skus input:radio").length) {
		$("#product-skus input:radio:checked").click();
	} else if($(".skus input:radio").length) {
		$(".skus input:radio:checked").click();
	}
	if($(".sku-feature").length) {
		$(".sku-feature").change();
	}
});

$(document).on("click","#product-skus input:radio",function () {
	var form = $(this).parents("form");
	if (typeof arrived_sku_count !== 'undefined' && parseInt(arrived_sku_count[$(this).val()])<=0) {
		$(".plugin_arrived-button.is-product-page").show();
		$(".plugin_arrived-box.is-product-page input[name='plugin_arrived_skuid']").val($(this).val());
	} else {
		$(".plugin_arrived-button.is-product-page").hide();
	}
});

$(document).on("click",".skus input:radio",function () {
	var form = $(this).parents("form");
	if (typeof arrived_sku_count !== 'undefined' && parseInt(arrived_sku_count[$(this).val()])<=0) {
		$(".plugin_arrived-button.is-product-page").show();
		$(".plugin_arrived-box.is-product-page input[name='plugin_arrived_skuid']").val($(this).val());
	} else {
		$(".plugin_arrived-button.is-product-page").hide();
	}
});

$(document).on("change",".sku-feature",function() {
	var key = "";
	$("select.sku-feature, input[type=radio]:checked.sku-feature, input[type=hidden].sku-feature").each(function() {
		key += $(this).data('feature-id') + ':' + $(this).val() + ';';
	});
	if(typeof arrived_sku_features == 'undefined')
		return false;
	var sku = arrived_sku_features[key];
	if (sku) {
		if (sku.available) {
			if(arrived_ignore_stock_count) {
				if(sku.count<=0 && sku.count!==null) {
					$(".plugin_arrived-button.is-product-page").show();
					$(".plugin_arrived-box.is-product-page input[name='plugin_arrived_skuid']").val(sku.id);
				} else {
					$(".plugin_arrived-button.is-product-page").hide();
				}
			} else {
				$(".plugin_arrived-button.is-product-page").hide();
			}
		} else {
			$(".plugin_arrived-button.is-product-page").show();
			$(".plugin_arrived-box.is-product-page input[name='plugin_arrived_skuid']").val(sku.id);
		}
	} else {
		$(".plugin_arrived-button.is-product-page").show();
	}
});

$(document).on("click",".plugin_arrived-button a", function () {
	var plugin_arrived_hidden = $(this).parent().next(".plugin_arrived-custom").clone();
	plugin_arrived_hidden.appendTo("body")
	.wrap("<div class='plugin_arrived-popup'></div>")
	.find(".plugin_arrived-box").wrap("<form action='" + plugin_arrived_hidden.find(".plugin_arrived-box").data('action') + "' class='plugin_arrived-form' onsubmit='return plugin_arrived_send();'></form>")
	.parents(".plugin_arrived-popup").after("<div class='plugin_arrived-overlay' onClick='plugin_arrived_close();'></div>")
	.find(".plugin_arrived-box").css({'margin-left':(-1)*Math.max(0, $(".plugin_arrived-popup .plugin_arrived-box").outerWidth() / 2)+'px','margin-top':(-1)*Math.max(0, $(".plugin_arrived-popup .plugin_arrived-box").outerHeight() / 2)+'px'})
	.find("input:submit").removeAttr('disabled').show();
});

function plugin_arrived_close() {
	$(".plugin_arrived-overlay, .plugin_arrived-popup").fadeOut(function() {
		$(this).remove();
	});
}

function plugin_arrived_send() {
	$(".plugin_arrived-popup .msg_errors").html("").hide();
	$(".plugin_arrived-popup .plugin_arrived-value.submit input").hide();
	$(".plugin_arrived-popup .plugin_arrived-value.submit .plugin_arrived-loading").show();
	$.post($(".plugin_arrived-popup form").attr('action'), $(".plugin_arrived-popup form").serialize(), function (response) {
		if (response.status == 'ok') {
			$(".plugin_arrived-popup .plugin_arrived-request").hide();
			$(".plugin_arrived-popup .plugin_arrived-success").show();
		} else if (response.status == 'fail') {
			$(".plugin_arrived-popup .msg_errors").html(response.errors).show();
		}
		$(".plugin_arrived-popup .plugin_arrived-value.submit input").show();
	$(".plugin_arrived-popup .plugin_arrived-value.submit .plugin_arrived-loading").hide();
	},"json");
	return false;
}