{extends file='frontend/index/index.tpl'}

{block name='frontend_index_content_left'}{/block}

{block name="frontend_index_header_javascript" append}
    <script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="{link file='frontend/_resources/javascript/g2apay.status_update.js'}"></script>
{/block}

{block name="frontend_index_header_css_print" append}
    <link type="text/css" media="screen, projection" rel="stylesheet"
          href="{link file='frontend/_resources/styles/g2apay.status_update.css'}"/>
{/block}

{* Breadcrumb *}
{block name='frontend_index_start' append}
    {$sBreadcrumb = [['name'=>$text_title]]}
{/block}

{* Main content *}
{block name="frontend_index_content"}
    <div id="g2apay__payment" class="grid_20 g2apay__payment" data-url="{$check_url}">

        <h2 class="headingbox_dark largesize">{$text_header}</h2>
        <div id="g2apay__payment-loader" class="ajaxSlider">
            <div class="loader">{$text_wait}</div>
        </div>
        <div id="g2apay__payment-message" class="g2apay__payment-message center bold">
        </div>
        <div class="actions">
            <a href="{$history_url}" title="{$text_history}" class="button-right right" rel="nofollow">
                <span>{$text_history}</span>
            </a>
            <div class="clear">&nbsp;</div>
        </div>
    </div>
    <div class="doublespace">&nbsp;</div>
{/block}
