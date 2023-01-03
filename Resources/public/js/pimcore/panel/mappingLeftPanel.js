pimcore.registerNS("pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.mappingLeftPanel");
pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.mappingLeftPanel = Class.create({
    getTabPanel: function () {
        if (!this.panel) {
            this.panel = new Ext.Panel({
                border: false,
                title: t('pimcore_psc_my_mapping'),
                layout: "border",
                items: [{
                    region: 'center',
                    layout: 'border',
                    items: [{
                        title: t('pimcore_psc_my_mapping'),
                        region: 'west',
                        width: 250,
                        id: 'pimcore_psc_my_mapping_id',
                        split: true,
                        layout: 'accordion',
                        header: false,
                        items: [this.getMappingTree()]
                    }, this.getMappingEditContainer()]
                }]
            });
            pimcore.layout.refresh();
        }
        return this.panel;
    },
    getMappingTree: function () {
        console.log(this.tree);
        if (!this.tree) {
            let mappingStore = Ext.create('Ext.data.TreeStore', {
                // Named Store
                storeId: 'dataStore',
                proxy: {
                    type: 'ajax',
                    url: '/admin/pimcoresalesforce/mapping/tree',
                    reader: {
                        type: 'json',
                        totalProperty: 'total',
                        rootProperty: 'nodes'
                    },
                    extraParams: {
                        limit: 15
                    }
                }
            });
            this.tree = Ext.create('Ext.tree.Panel', {
                title: t('pimcore_psc_my_mapping'),
                store: mappingStore,
                rootVisible: false,
                autoScroll: true,
                containerScroll: true,
                root: {
                    id: '0',
                    iconCls: "pimcore_icon_home",
                    text: t('home'),
                    expanded: true,
                    reload: true,
                    draggable: false,
                    allowChildren: true,
                },
                listeners: this.getTreeNodeListeners(),
                tbar: {
                    items: [{
                        text: t('pimcore_psc_add_channel'),
                        iconCls: "pimcore_icon_add",
                        handler: this.addNewMappingForm.bind(this)
                    }]
                }
            });
        }
        return this.tree;
    },
    getMappingEditContainer: function() {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: "center"
            });
        }
        return this.editPanel;

    },
    addNewMappingForm: function () {

        Ext.MessageBox.prompt(t('pimcore_psc_input_title'),
            t('pimcore_psc_input_label'),
            this.addNewMapping.bind(this), null, null, "");
    },
    getTreeNodeListeners: function (treeType) {
        treeNodeListeners = {
            'itemclick': this.onTreeNodeClick.bind(this),
            'beforeitemappend': function (thisNode, newChildNode,
                index, eOpts) {
                    console.log('-----------------');
                newChildNode.data.leaf = true;
                newChildNode.data.expaned = true;
                newChildNode.data.iconCls = "pimcore_icon_link"
            }

        }
        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts) {
        console.log(record)
        if (record.data.id > 0) {
            this.openTabPanel(record.data.id, record.data.parentId);
        }
    },
    addNewMapping: function (button, value, object) {
        if (button === 'ok') {
            if (typeof value != "undefined" && value != null && value != '') {
                console.log(value);
                if (pimcore.helpers.isValidFilename(value)) {
                    Ext.Ajax.request({
                        url: "/admin/pimcoresalesforce/mapping/add",
                        params: {
                            name: value
                        },
                        success: function (response) {
                            const result = Ext.decode(response.responseText);
                            if (result.success) {
                                this.openTabPanel(result.id, 0);
                            } else if (result.success === false && result.id) {
                                pimcore.helpers.showNotification(t("error"), t("psc_name_already_in_use"));
                            } else {
                                pimcore.helpers.showNotification(t('error'), t('error_info_msg'), 'error');
                            }
                            this.tree.getStore().load({
                                node: this.tree.getRootNode()
                            });
                        }.bind(this)
                    });
                } else {
                    pimcore.helpers.showNotification(t('error'), t('psc_invalid_name'), 'error');
                }
            } else {
                pimcore.helpers.showNotification(t('error'), t('psc_key_required'), 'error');
            }
        }
    },
    openTabPanel: function (id, parentTypeId) {
        const existingPanel = Ext.getCmp("syncrasy_salesforce_mapping_panel_" + id);

        if (existingPanel) {
            this.editPanel.setActiveTab(existingPanel);
            return;
        }
        pimcore.helpers.loadingShow();
        Ext.Ajax.request({
            url: "/admin/pimcoresalesforce/mapping/get",
            params: {
                id: id
            },
            success: function (response) {
                pimcore.helpers.loadingHide();
                let data = Ext.decode(response.responseText);
                data.columnAttributeMapping = Ext.decode(data.data.columnAttributeMapping);
                var fieldPanel = new pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.configItem(data, id, this, parentTypeId);
            }.bind(this)
        });
    }

});