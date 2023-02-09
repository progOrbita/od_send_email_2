<!-- Block od_send_email -->
{$err}
<div class="tab-content">
    <ul class="nav nav-tabs" role="tablist">
        
    {foreach item=tab from=$tabs}
    <li class="nav-item {$tab.class_active}">
        <a href="#{$tab.id}" role="tab" data-toggle="tab">{$tab.tittle}</a>
    </li>
    {/foreach}

    </ul>
    {foreach item=tab from=$tabs}
    <div class="tab-pane {$tab.class_active} {$tab.class_in} fade" id="{$tab.id}">
        {$tab.content}
    </div>
    {/foreach}
</div>
<!-- /Block od_send_email -->
