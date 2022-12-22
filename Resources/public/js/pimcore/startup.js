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
        if (typeof object.edit != 'undefined' && object.data.general.o_className == 'SelesForceSetup') {
            var pimClassChange = object.edit.dataFields.pimcoreclass.component;
            var objectData = object.data.data.fieldmapping;
            var pimClassFields;
            var sfObjectFields;
            var sfObjectChange = object.edit.dataFields.salesforceobject.component;
            var tabPanel = object.edit.layout.items.items[0];

            SyncrasyPimcoreSalesforceBundlePlugin.getSfClassAjax(function (store) {
                sfObjectChange.setStore(store);
                sfObjectChange.setValue(object.data.data.salesforceobject);
                if (sfObjectChange.getValue() != '') {
                    SyncrasyPimcoreSalesforceBundlePlugin.getSfFieldAjax(sfObjectChange.getValue(), function (store) {
                        sfObjectFields = store;
                        SyncrasyPimcoreSalesforceBundlePlugin.setFieldMapping(tabPanel.items.items[1], pimClassFields, sfObjectFields, objectData);
                    });
                }
            });
            SyncrasyPimcoreSalesforceBundlePlugin.getPimClassAjax(function (store) {
                pimClassChange.setStore(store);
                pimClassChange.setValue(object.data.data.pimcoreclass);
                if (pimClassChange.getValue() != '') {
                    SyncrasyPimcoreSalesforceBundlePlugin.getPimFieldAjax(pimClassChange.getValue(), function (store) {
                        pimClassFields = store;
                        SyncrasyPimcoreSalesforceBundlePlugin.setFieldMapping(tabPanel.items.items[1], pimClassFields, sfObjectFields, objectData);
                    });
                }
            });


            pimClassChange.addListener('select', function (combo, record, eOpts) {
                if (record.data.value != '') {
                    SyncrasyPimcoreSalesforceBundlePlugin.getPimFieldAjax(record.data.value, function (store) {
                        pimClassFields = store;
                    });
                }
            });
            sfObjectChange.addListener('select', function (combo, record, eOpts) {
                if (record.data.value != '') {
                    SyncrasyPimcoreSalesforceBundlePlugin.getSfFieldAjax(record.data.value, function (store) {
                        sfObjectFields = store;
                    });
                }
            });



            var tabPanel = object.edit.layout.items.items[0];

            tabPanel.addListener('tabchange', function (tabPanel, newTab, oldTab, eOpts) {
                if (newTab.title == 'Fields Mapping') {
                    SyncrasyPimcoreSalesforceBundlePlugin.setFieldMapping(newTab, pimClassFields, sfObjectFields, objectData);
                }
            });
        }

    },

    setFieldMapping: function (newTab, pimClassFields, sfObjectFields, objectData) {


        if (newTab.items.items.length > 0) {
            newTab.items.items[0].addListener('add', function (panelAdd, addBlock) {
                if (addBlock.items.items.length > 1) {
                    addBlock.items.items[1].items.items[0].setStore(pimClassFields);
                    addBlock.items.items[1].items.items[1].setStore(sfObjectFields);
                }
            });
            var i = 0;
            if (newTab.items.items[0].items.items.length > 0) {
                newTab.items.items[0].items.items.forEach(function (item) {
                    if (pimClassFields !== undefined) {
                        item.items.items[1].items.items[0].setStore(pimClassFields);
                        if (objectData[i] !== undefined)
                            item.items.items[1].items.items[0].setValue(objectData[i].data.pimcoreclassfield);
                    }
                    if (sfObjectFields) {
                        item.items.items[1].items.items[1].setStore(sfObjectFields);
                        if (objectData[i] !== undefined)
                            item.items.items[1].items.items[1].setValue(objectData[i].data.salesforceobjectfield);
                    }
                    i++;
                });
            }
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
