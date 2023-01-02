pimcore.registerNS("pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.tabs.columnConfiguration");
pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.tabs.columnConfiguration = Class.create(pimcore.element.abstract, {
    initialize: function (config, parentType) {
        this.mappingAttributeSetting = config.columnAttributeMapping; 
        this.lang = config.data.lang;
        this.config = this.mappingAttributeSetting;
        this.channelPimId = config.general.o_id;
        this.parentType = parentType;
        this.allowedOperatorList = [
            "Ignore",
            "AnyGetter",
            "Arithmetic",
            "Comparison",
            "DateFormatter",
            "FieldCollectionGetter",
            "Boolean",
            "Concatenator",
            "Trimmer",
            "CharCounter",
            "ObjectBrickGetter",
            "ObjectFieldGetter",
            "PropertyGetter",
            "Substring",
            "StringReplace",
            "StringContains",
            "Base64",
            "CaseConverter",
            "Multiselect",
            "Fixed",
            "BooleanFormatter",
            "MediaGetter",
            "LocaleSwitcher",
            "CollectionMediaGetter"
        ];
        this.configPanel = new Ext.Panel({
            layout: "border",
            iconCls: "dHub_column_mapping",
            title: t("dHub_column_configuration"),
            buttons: {
                itemId: 'distributionhub_column_configuration_footer'
            },
            items: []
        });

        this.rebuildPanel();
        this.getConfigButton();
    },

    rebuildPanel: function () {
        //this.configPanel.removeAll(true);
      //  this.selectionPanel = null;
       // this.leftPanel = null;
       // this.classDefinitionTreePanel = null;
       // this.configPanel.add(this.getSelectionPanel());
      //  this.configPanel.add(this.getLeftPanel());
    },
    reloadPanel: function (responseData) {
      //  this.config = responseData;
      //  this.buildDefaultSelection();
      //  this.configPanel.removeAll(true);
     //   this.selectionPanel = null;
     //   this.leftPanel = null;
     //   this.classDefinitionTreePanel = null;
       // this.configPanel.add(this.getSelectionPanel());
      //  this.configPanel.add(this.getLeftPanel());
    },
    getConfigButton: function () {
        var footer = this.configPanel.getDockedComponent('distributionhub_column_configuration_footer');
        footer.removeAll();
        footer.add({
            text: t("dHub_reset_mapping"),
            iconCls: "pimcore_icon_refresh",
            hidden:(typeof this.parentType != "undefined" && this.parentType == 'shared'),
           // handler: this.resetMapping.bind(this)
        });
        footer.add({
            text: t("dHub_import_header"),
            iconCls: "pimcore_icon_start_import",
            hidden:(typeof this.parentType != "undefined" && this.parentType == 'shared'),
          //  handler: this.importConfigHeaders.bind(this, this.channelPimId)
        });
        footer.add({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            hidden:(typeof this.parentType != "undefined" && this.parentType == 'shared'),
         //   handler: this.saveChannelMappingConfig.bind(this)
        });
    },
    getPanel: function () {
        return this.configPanel;
    },


});
