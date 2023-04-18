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
                text: t("psc_plugin"),
                iconCls: "psc_icon",
                cls: "pimcore_main_menu",
                handler: this.showSalesforceConnector.bind(this)
            }
            );
        }
        if(extrasMenu){
            extrasMenu.updateLayout();
        }
        this.getConfig();
    },
    showSalesforceConnector: function(config){
       
        console.log(pimcore.globalmanager.get("psc_plugin_cnf"));
        console.log(Ext.getCmp("pimcore_plugin_psc_panel"));
        if (pimcore.globalmanager.get("psc_plugin_cnf")) {
            return Ext.getCmp("pimcore_panel_tabs").setActiveItem("pimcore_plugin_psc_panel");
        } else {
            return pimcore.globalmanager.add("psc_plugin_cnf", new pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.main(config));
        }

    },
    getConfig : function(){

    }

});

var SyncrasyPimcoreSalesforceBundlePlugin = new pimcore.plugin.SyncrasyPimcoreSalesforceBundle();
