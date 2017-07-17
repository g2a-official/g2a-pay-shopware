/**
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

////{block name="backend/order/view/detail/window" append}
//{namespace name=backend/order/view/main}
Ext.define('Shopware.apps.TransactionG2APay.view.detail.Window', {
    override      : 'Shopware.apps.Order.view.detail.Window',
    createTabPanel: function() {
        var me = this;
        var tabPanel = me.callParent(arguments);
        if (me.displayTab()) {
            tabPanel.add(Ext.create('Shopware.apps.TransactionG2APay.view.detail.Panel', {
                title             : 'G2A Pay',
                id                : 'pmOrderOperationsTab',
                historyStore      : me.historyStore,
                record            : me.record,
                orderStatusStore  : me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
            }));
        }
        return tabPanel;
    },
    displayTab    : function() {
        var id = this.record.get('id');
        var result = false;
        Ext.Ajax.request({
            url    : '{url controller=TransactionG2APay action=displayTab}',
            method : 'POST',
            async  : false,
            params : {
                orderId: id
            },
            success: function(response) {
                var decodedResponse = Ext.decode(response.responseText);
                result = decodedResponse.success;
            }
        });
        return result;
    }
});
//{/block}
