pimcore.registerNS("pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.configItem");
pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.configItem = Class.create(pimcore.element.abstract, {
    initialize: function (data, pimId, parent, parentType) {
        console.log(data, pimId, parent, parentType)
        this.parent = parent;
        this.parentType = parentType;
        this.pimId = pimId;
        this.data = data;
        this.mappingAttributeSetting = {};
        this.modificationDate = data.modificationDate;
        this.tab = new Ext.TabPanel({
            activeTab: 0,
            title: this.data.general.o_key,
            closable: true,
            deferredRender: false,
            forceLayout: true,
            iconCls: "pimcore_icon_link",
            id: "syncrasy_salesforce_mapping_panel_" + this.data.general.o_id,
            buttons: {
                itemId: 'footer'
            },
            items:
                [
                    this.getBasicConfiguration(),
                    this.getColumnConfiguration()
                ]
        });
        this.tab.on("activate", this.tabactivated.bind(this));
        this.tab.on("destroy", this.tabdestroy.bind(this));

        this.parent.editPanel.add(this.tab);
        this.parent.editPanel.setActiveTab(this.tab);
        this.parent.editPanel.updateLayout();
    },
    getBasicConfiguration: function () {
        let basicConfig = new pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.tabs.basicConfig(this.data, this.pimId, this.parent, this.parentType);
        return basicConfig.getFormPanel();
    },
    getColumnConfiguration: function () {
        this.mappingAttributeSetting = this.data.columnAttributeMapping;
        this.columnMappingPanel = new pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.tabs.columnConfiguration(this.data, this.parentType);
        return this.columnMappingPanel.getPanel();
    },
    tabactivated: function () {
       // this.setupChangeDetector();
        this.tabdestroyed = false;
    },

    tabdestroy: function () {
        this.tabdestroyed = true;
    },

});