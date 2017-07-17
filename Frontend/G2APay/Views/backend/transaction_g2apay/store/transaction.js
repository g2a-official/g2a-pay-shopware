/**
 * (c) G2A
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Ext.define('Shopware.apps.TransactionG2APay.store.Transaction', {
    extend    : 'Ext.data.Store',
    model     : 'Shopware.apps.TransactionG2APay.model.Transaction',
    autoLoad  : false,
    remoteSort: false
});
