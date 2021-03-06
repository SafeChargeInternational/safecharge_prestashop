<div class="panel">
    <div class="panel-heading">
        <i class="icon-file-text"></i>
        {l s="Nuvei notes" mod='nuvei'} <span class="badge">{$messages|count}</span>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><span class="title_box ">{l s="Date" mod='nuvei'}</span></th>
                    <th><span class="title_box ">{l s="Message" mod='nuvei'}</span></th>
                </tr>
            </thead>
            
            {if $messages|count gt 0}
                <tbody>
                    {foreach $messages as $msg}
                        <tr>
                            <td>{$msg.date_add}</td>
                            <td>{$msg.message}</td>
                        </tr>
                    {/foreach}
                </tbody>
            {/if}
        </table>
    </div>
</div>