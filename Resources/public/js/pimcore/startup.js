pimcore.registerNS("pimcore.plugin.SyncrasyPimcoreSalesforceBundle");

pimcore.plugin.SyncrasyPimcoreSalesforceBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.SyncrasyPimcoreSalesforceBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params,broker){
        var extrasMenu = pimcore.globalmanager.get("layout_toolbar").extrasMenu;
        if(extrasMenu){
            extrasMenu.insert(extrasMenu.items.length+1, {
                text: t("plugin_psc"),
                iconCls: "plugin_pmicon",
                cls: "pimcore_main_menu",
                handler: this.showSalesforceConnector.bind(this)
            });
        }
        if(extrasMenu){
            extrasMenu.updateLayout();
        }

        this.getConfig();
    },
    showSalesforceConnector: function(config){
        config = defaultValue(config,{});
        if (pimcore.globalmanager.get("plugin_psc_cnf")) {
            return Ext.getCmp("pimcore_panel_tabs").setActiveItem("pimcore_plugin_psc_panel");
        } else {
            return pimcore.globalmanager.add("plugin_pplugin_psc_cnfm_cnf", new pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.main(config));
        }

    },
    getConfig : function(){

    }

});

var SyncrasyPimcoreSalesforceBundlePlugin = new pimcore.plugin.SyncrasyPimcoreSalesforceBundle();
