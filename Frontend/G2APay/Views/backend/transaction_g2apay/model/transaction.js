/**
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Ext.define('Shopware.apps.TransactionG2APay.model.Transaction', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'transactionId',
            type: 'string'
        },
        {
            name: 'status',
            type: 'string'
        },
        {
            name: 'refundedAmount',
            type: 'string'
        },
        {
            name: 'createTime',
            type: 'string'
        },
        {
            name: 'isSubscription',
            type: 'string'
        },
        {
            name: 'subscriptionId',
            type: 'string'
        },
    ],
    proxy : {
        type  : 'ajax',
        api   : {
            read: '{url controller=TransactionG2APay action=loadStore}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
