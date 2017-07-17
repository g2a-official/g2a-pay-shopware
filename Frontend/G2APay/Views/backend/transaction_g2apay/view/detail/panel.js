/**
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//{namespace name=backend/order/main}
Ext.require([
    'Ext.grid.*', 'Ext.data.*', 'Ext.panel.*'
]);

//{block name="backend/order/view/detail/g2apay"}
Ext.define('Shopware.apps.TransactionG2APay.view.detail.Panel', {

    extend          : 'Ext.form.Panel',
    autoScroll      : true,
    initComponent   : function() {
        var me = this;
        var id = me.record.get('id');
        var store = Ext.create('Shopware.apps.TransactionG2APay.store.Transaction');
        me.items = [
            Ext.create('Ext.panel.Panel', {
                width    : '100%',
                height   : '100%',
                bodyStyle: {
                    background: '#F0F2F4'
                },
                items    : [
                    Ext.create('Ext.grid.Panel', {
                        id       : 'transactionGrid',
                        store    : store.load({
                            params: {
                                'orderId': id
                            }
                        }),
                        listeners: {
                            activate: function(tab) {
                                var me = this;
                                var store = store.load({
                                    params: {
                                        'orderId': id
                                    }
                                });
                                me.reconfigure(store);
                            }
                        },
                        columns  : [
                            {
                                header   : 'Transaction ID',
                                dataIndex: 'transactionId',
                                flex     : 1
                            },
                            {
                                header   : 'Transaction Status',
                                dataIndex: 'status',
                                flex     : 1
                            },
                            {
                                header   : 'Refunded Amount',
                                dataIndex: 'refundedAmount',
                                flex     : 1
                            },
                            {
                                header   : 'Transaction Time',
                                dataIndex: 'createTime',
                                flex     : 1
                            },
                            {
                                header   : 'Is Subscription Payment',
                                dataIndex: 'isSubscription',
                                flex     : 1
                            },
                            {
                                header   : 'Subscription Id',
                                dataIndex: 'subscriptionId',
                                flex     : 1
                            },
                            {
                                xtype : 'actioncolumn',
                                header: 'Refund',
                                items : [
                                    {
                                        action  : 'refund',
                                        tooltip : 'Refund',
                                        getClass: function(value, metaData, record) {
                                            if (record.get('status') !== 'refunded') {
                                                return 'sprite-arrow-return-180';
                                            }
                                        },
                                        handler : function(view, rowIndex, colIndex, item, opts, record) {
                                            if (record.get('status') !== 'refunded') {
                                                me.refund(record);
                                            }
                                        },
                                    },
                                ]
                            },
                        ]
                    }), {
                        xtype : 'fieldset',
                        width : '100%',
                        id    : 'refundContainer',
                        hidden: true,
                        layout: {
                            type : 'hbox',
                            pack : 'end',
                            align: 'middle'
                        },
                        items : [
                            {
                                xtype     : 'base-element-number',
                                name      : 'refundAmount',
                                id        : 'refundAmount',
                                fieldLabel: 'Refund amount:'
                            },
                            {
                                xtype: 'splitter'
                            },
                            {
                                xtype  : 'button',
                                text   : 'Refund',
                                hidden : true,
                                handler: function() {
                                    me.refund();
                                }
                            }
                        ]
                    }
                ]
            })
        ];
        this.callParent(arguments);
        this.refreshRefund();
    },

    refreshRefund   : function() {
        this.setLoading(true);
        var id = this.record.get('id');
        var container = Ext.ComponentManager.get('refundContainer');
        container.hide();
        var me = this;
        Ext.Ajax.request({
            url    : '{url controller=TransactionG2APay action=refundInfo}',
            method : 'POST',
            params : {
                orderId: id
            },
            success: function(response) {
                var data = Ext.decode(response.responseText);
                if (data.success && data.canRefund) {
                    Ext.ComponentManager.get('refundAmount').setValue(0);
                    container.show();
                }
                me.setLoading(false);
            }
        });
    },

    refund          : function(record) {
        this.setLoading(true);
        var container = Ext.ComponentManager.get('refundContainer');
        container.hide();
        var id = this.record.get('id');
        var transactionId = record.get('transactionId');
        var refundAmount = Ext.ComponentManager.get('refundAmount').getValue();
        var me = this;
        Ext.Ajax.request({
            url    : '{url controller=TransactionG2APay action=refund}',
            method : 'POST',
            params : {
                orderId      : id,
                refundAmount : refundAmount,
                transactionId: transactionId
            },
            success: function(response) {
                var data = Ext.decode(response.responseText);
                var messageText = data.message;
                if (data.success) {
                    var store = Ext.create('Shopware.apps.TransactionG2APay.store.Transaction');
                    Ext.ComponentManager.get('transactionGrid').reconfigure(store.load({
                        params: {
                            'orderId': id
                        }
                    }));
                }
                me.showNotification('G2A Pay', messageText);
                me.setLoading(false);
                me.refreshRefund();
            }
        });
    },

    showNotification: function(title, message) {
        if (typeof Shopware.Notification.createStickyGrowlMessage == 'function') {
            Shopware.Notification.createStickyGrowlMessage({
                title: title,
                text : message
            });
        } else {
            Shopware.Notification.createGrowlMessage(title, message);
        }
    }
});
//{/block}
