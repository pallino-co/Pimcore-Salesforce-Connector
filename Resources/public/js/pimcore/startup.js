pimcore.registerNS("pimcore.plugin.SyncrasyPimcoreSalesforceBundle");

pimcore.plugin.SyncrasyPimcoreSalesforceBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.SyncrasyPimcoreSalesforceBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        // alert("SyncrasyPimcoreSalesforceBundle ready!");
    },
    postOpenObject: function (object, type) {
        console.log('-------------');
        if (typeof object.edit != 'undefined' && object.data.general.o_className == 'SelesForceSetup') {
            var pimClassChange = object.edit.dataFields.pimcoreclass.component;
            var objectData = object.data.data.fieldmapping;
            console.log('-------------',object.data.data);
            var pimClassFields;
            var sfObjectChange = object.edit.dataFields.salesforceobject.component;
            SyncrasyPimcoreSalesforceBundlePlugin.getSfClassAjax(function(store){
                sfObjectChange.setStore(store);
                sfObjectChange.setValue(object.data.data.salesforceobject);
                console.log('------sfObjectChange.getValue()-------',sfObjectChange.getValue());
                if(sfObjectChange.getValue()!='') {
                SyncrasyPimcoreSalesforceBundlePlugin.getSfFieldAjax(sfObjectChange.getValue(), function (store) {
                    sfObjectFields = store;
                });
            }
                
            });
            SyncrasyPimcoreSalesforceBundlePlugin.getPimClassAjax(function(store){
                pimClassChange.setStore(store);
                pimClassChange.setValue(object.data.data.pimcoreclass);
                if(pimClassChange.getValue() != '') {
                SyncrasyPimcoreSalesforceBundlePlugin.getPimFieldAjax(pimClassChange.getValue(), function (store) {
                    pimClassFields = store;
                });
            }
            });
            pimClassChange.addListener('select', function (combo, record, eOpts) {
                if(record.data.value != '') {
                    SyncrasyPimcoreSalesforceBundlePlugin.getPimFieldAjax(record.data.value, function (store) {
                        pimClassFields = store;
                    });
                }
            });
            
            


            
            
            var sfObjectFields;
            sfObjectChange.addListener('select', function (combo, record, eOpts) {
                if(record.data.value != '') {
                    SyncrasyPimcoreSalesforceBundlePlugin.getSfFieldAjax(record.data.value, function (store) {
                        sfObjectFields = store;
                    });
                }
            });
            
           


            var tabPanel = object.edit.layout.items.items[0];
            tabPanel.addListener('tabchange', function (tabPanel, newTab, oldTab, eOpts) {
                if (newTab.title == 'Fields Mapping') {
                    newTab.items.items[0].addListener('add', function (panelAdd, addBlock) {
                        if (addBlock.items.items.length > 1) {
                            addBlock.items.items[1].items.items[0].setStore(pimClassFields);
                            addBlock.items.items[1].items.items[1].setStore(sfObjectFields);
                        }
                    });
                    var i = 0;
                    
                    newTab.items.items[0].items.items.forEach(function (item) {
                        item.items.items[1].items.items[0].setStore(pimClassFields);
                        if(objectData[i] !== undefined)
                        item.items.items[1].items.items[0].setValue(objectData[i].data.pimcoreclassfield);

                        item.items.items[1].items.items[1].setStore(sfObjectFields);
                        if(objectData[i] !== undefined)
                        item.items.items[1].items.items[1].setValue(objectData[i].data.salesforceobjectfield);
                        i++;
                    });
                }
            });
        }

    },

    getPimFieldAjax: function (classVal, callback) {
        var pimClassFields;
        Ext.Ajax.request({
            method: 'get',
            url: "/pimfields/" + classVal,
            success: function (response) {
                let data = Ext.JSON.decode(response.responseText);
                if (data.success) {
                    options = data.data;
                    console.log('--------options-------',options);
                    pimClassFields = Ext.create('Ext.data.Store', {
                        fields: ['value', 'key'],
                        data: options
                    });
                    return callback(pimClassFields);
                }
            },
            failure: function () {
                console.log('failure');
            }
        });

    },
    getPimClassAjax: function (callback) {
        var pimClassFields;
        Ext.Ajax.request({
            method: 'get',
            url: "/pim-object",
            success: function (response) {
                let data = Ext.JSON.decode(response.responseText);
                if (data.success) {
                    options = data.data;
                    pimClassFields = Ext.create('Ext.data.Store', {
                        fields: ['value', 'key'],
                        data: options
                    });
                    return callback(pimClassFields);
                }
            },
            failure: function () {
                console.log('failure');
            }
        });

    },
    getSfClassAjax: function (callback) {
        var pimClassFields;
        Ext.Ajax.request({
            method: 'get',
            url: "/sf-object",
            success: function (response) {
                let data = Ext.JSON.decode(response.responseText);
                if (data.success) {
                    options = data.data;
                    pimClassFields = Ext.create('Ext.data.Store', {
                        fields: ['value', 'key'],
                        data: options
                    });
                    return callback(pimClassFields);
                }
            },
            failure: function () {
                console.log('failure');
            }
        });

    },
    getSfFieldAjax: function (classVal, callback) {
        var pimClassFields;
        Ext.Ajax.request({
            method: 'get',
            url: "/sffields/" + classVal,
            success: function (response) {
                let data = Ext.JSON.decode(response.responseText);
                if (data.success) {
                    options = data.data;
                    pimClassFields = Ext.create('Ext.data.Store', {
                        fields: ['value', 'key'],
                        data: options
                    });
                    return callback(pimClassFields);
                }
            },
            failure: function () {
                console.log('failure');
            }
        });

    }
    
});

var SyncrasyPimcoreSalesforceBundlePlugin = new pimcore.plugin.SyncrasyPimcoreSalesforceBundle();
