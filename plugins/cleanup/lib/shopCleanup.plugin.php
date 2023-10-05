<?php

class shopCleanupPlugin extends shopPlugin
{
    public function backendOrder($params)
    {
        if ($params['state']->getId() != 'deleted'||!wa()->getUser()->isAdmin('shop')) return;
        $html = '<a href="#" id="cleanup-delete"><i class="icon16 delete"></i>'._wp('Remove order').'</a>
        <script>$("#cleanup-delete").click(function(e){
            e.preventDefault();
            if (confirm("'._wp('Are you sure you want to remove').'?")) {
                $.post("?plugin=cleanup&action=deleteOrder", {order:'.$params['id'].'});
                if ($("#order-list").find("[data-order-id].selected").next().length > 0) {
                    $("#order-list").find("[data-order-id].selected").next().find("a")[0].click();
                } else {
                    $(".lazyloading-progress-string").text("");
                    $("#s-sidebar").find(".selected").find("a")[0].click();
                }
                $.order_list.hideListItems('.$params['id'].');
            }
        });</script>';
        return array('action_link' => $html);
    }
}
