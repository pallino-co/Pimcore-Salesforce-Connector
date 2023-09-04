pimcore.registerNS("pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.tabs.basicConfig");
pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.tabs.basicConfig = Class.create(pimcore.element.abstract, {
    initialize: function (data, mappingId, parentPanel, parentType) {
        console.log('hSVHJDVCUHV  UVDHC',data);
        this.data = data.data;
        this.mappingId = mappingId;
        this.parentPanel = parentPanel;
        this.parentType = parentType;
        this.pimcoreClassId = this.data[0].pimcoreClassId ? this.data[0].pimcoreClassId : null;
        this.salesforceObjectId = this.data[0].salesforceObject ? this.data[0].salesforceObject : null;
        this.fieldForSfId = this.data[0].fieldForSfId ? this.data[0].fieldForSfId : null;
        this.sfUniqueField = this.data[0].salesforceUniqueField ? this.data[0].salesforceUniqueField : null;
        this.pimUniqueField = this.data[0].pimcoreUniqueField ? this.data[0].pimcoreUniqueField : null;
        this.importFolderPath = this.data[0].importFilePath ? this.data[0].importFilePath : null;
        this.type = this.data[0].type ? this.data[0].type : null;
        this.importFolderId = this.data[0].importFilePathId ? this.data[0].importFilePathId : null;


    },
    /**
     * create and return the form
     *
     * @returns {Ext.form.FormPanel}
     */
    getFormPanel: function () {

        let pimclassCombo = this.getPimClassCombo()
        let getSalesforceObjectCombo = this.getSalesforceObjectCombo()
        let fieldForSfId = this.getFieldForSfId()
        let pimcoreUniqueField = this.getPimcoreUniqueField()
        let salesforceUniqueField = this.getSalesforceUniqueField()
        let importFilePath = this.getImportFilePathField()
        let items = [];
        items.push(pimclassCombo, getSalesforceObjectCombo, fieldForSfId, pimcoreUniqueField, salesforceUniqueField)
        if(this.type === 'import'){
            items.push(importFilePath)
        }
        
        this.getImportFilePathField()
        let channelName = this.data[0].key;
        let infoImage = "/bundles/pimcoreadmin/img/flat-color-icons/info.svg";
        let infoMsg = "<img src='" + infoImage + "' style='vertical-align: middle' > " + "<span><i>" + t('psc_channel_name_append_msg1') + "'" + channelName + "'" + t('psc_channel_name_append_msg2') + "</i></span>";

        let infoPanel = new Ext.create('Ext.panel.Panel', {
            style: {
                paddingLeft: '255px',
                marginTop: '5px',
            },
            html: infoMsg,
        });
        this.basicForm = new Ext.form.FormPanel({
            bodyStyle: "padding:10px;",
            autoScroll: true,
            defaults: {
                labelWidth: 150,
                // width: 600
            },
            waitTitle: t('please_wait'),
            border: false,
            iconCls: 'psc_basic_config',
            title: t("psc_basic_configuration"),
            items: items,
            dockedItems: [
                {
                    xtype: 'toolbar',
                    flex: 1,
                    dock: 'bottom',
                    ui: 'footer',
                    layout: {
                        pack: 'end',
                        type: 'hbox'
                    },
                    items: [
                        {
                            xtype: 'button',
                            text: t('save'),
                            itemId: 'save',
                            cls: 'pimcore_save_button',
                            iconCls: 'pimcore_icon_apply',
                            handler: this.saveCallBack.bind(this)
                        }
                    ]
                }
            ],
        });
        return this.basicForm;
    },
    saveCallBack: function (saveCall) {
        saveCall.setDisabled(true);
        let form = saveCall.up('form').getForm();
        let outerScope = this
        if (form.isValid()) {
            form.submit({
                url: '/admin/pimcoresalesforce/default/save-basic-config',
                params: {
                    mappingId: this.mappingId,
                    csrfToken: pimcore.settings['csrfToken']
                },
                success: function (form, response) {
                    saveCall.setDisabled(false);
                    pimcore.helpers.showNotification(t('psc_success'), t('psc_basic_config_saved_successfully'), 'success');
                    // reload code
                    outerScope.reloadChannel(outerScope.mappingId, 0);
                },
                failure: function (form, response) {
                    response = response.response;
                    response = Ext.decode(response.responseText);
                    saveButton.setDisabled(false);
                    Ext.MessageBox.alert(t('psc_failure'), t(response.msg));
                }
            });
        }

    },

    reloadChannel: function (id, parentTypeId) {
        const existingPanel = Ext.getCmp("syncrasy_salesforce_mapping_panel_" + id);

        if (existingPanel) {
            existingPanel.destroy();
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
                var fieldPanel = new pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.configItem(data, id, this.parentPanel, parentTypeId);
            }.bind(this)
        });

    },

    getPimClassCombo: function () {
        let availableClasses = new Ext.data.JsonStore({
            proxy: {
                type: 'ajax',
                url: '/admin/pimcoresalesforce/default/pim-object',
                reader: {
                    type: 'json',
                    rootProperty: 'classes'
                }
            },
            fields: ["id", "name"],
            sorters: [{
                property: 'name',
                direction: 'ASC'
            }]
        });
        availableClasses.load();
        let combo = this.getCombo(availableClasses, 'psc_select_pimcore_class', 'pimcoreClassId' + this.mappingId);
        combo.value = this.pimcoreClassId;
        let outerScope = this;
        combo.on('select', function (combo, value, index) {
            var input = combo.getValue();
            outerScope.getPimcoreFieldStore(input);

        });
        return combo;

    },

    getSalesforceObjectCombo: function () {
        let availableClasses = new Ext.data.JsonStore({
            proxy: {
                type: 'ajax',
                url: '/admin/pimcoresalesforce/default/sf-object',
                reader: {
                    type: 'json',
                    rootProperty: 'objects'
                }
            },
            fields: ["id", "name"],
            sorters: [{
                property: 'name',
                direction: 'ASC'
            }]
        });
        availableClasses.load();
        // Create the combo box, attached to the states data store
        let combo = this.getCombo(availableClasses, 'psc_select_salesforce_object', 'salesforceObjectId' + this.mappingId);
        combo.value = this.salesforceObjectId;
        let outerScope = this;
        combo.on('select', function (combo, value, index) {
            var input = combo.getValue();
            outerScope.getSalesforceFieldStore(input);

        });
        return combo;

    },

    getPimcoreFieldStore: function (className,) {
        let outerScope = this;
        Ext.Ajax.request({
            url: `/admin/pimcoresalesforce/default/pimfields/${className}`,
            success: function (response) {
                var rdata = Ext.decode(response.responseText);
                console.log(outerScope.mappingId)
                var childCombo = Ext.getCmp('fieldForSfId' + outerScope.mappingId);
                childCombo.getStore().loadData(rdata.fields)
                childCombo.setValue('')
                var childCombo1 = Ext.getCmp('pimUniqueField' + outerScope.mappingId);
                childCombo1.getStore().loadData(rdata.fields)
                childCombo1.setValue('')
            }
        });
    },

    getSalesforceFieldStore: function (objectName) {
        let outerScope = this;
        Ext.Ajax.request({
            url: `/admin/pimcoresalesforce/default/sffields/${objectName}`,
            success: function (response) {
                var rdata = Ext.decode(response.responseText);
                var childCombo = Ext.getCmp('sfUniqueField' + outerScope.mappingId);
                childCombo.getStore().loadData(rdata.objects)
                childCombo.setValue('')
            }
        });
    },


    getPimcoreUniqueField: function () {
        let availableClasses = new Ext.data.JsonStore({
            fields: ['id', 'name'],
            proxy: {
                type: 'ajax',
                url: `/admin/pimcoresalesforce/default/pimfields/${this.pimcoreClassId}`,
                reader: {
                    type: 'json',
                    rootProperty: 'fields'
                }
            },
            listeners: {
                load: function (outher, records, successful, operation, eOpts) {
                    console.log(' b kjfgdv  ');
                }
            }
        });
        availableClasses.load();
        let combo = this.getCombo(availableClasses, 'psc_select_pim_unique_field', 'pimUniqueField' + this.mappingId);
        combo.value = this.pimUniqueField;

        return combo;

    },
    getSalesforceUniqueField: function () {
        let availableClasses = new Ext.data.JsonStore({
            fields: ['id', 'name'],
            proxy: {
                type: 'ajax',
                url: `/admin/pimcoresalesforce/default/sffields/${this.salesforceObjectId}`,
                reader: {
                    type: 'json',
                    rootProperty: 'objects'
                }
            },

        });
        availableClasses.load();
        let combo = this.getCombo(availableClasses, 'psc_select_sf_unique_field', 'sfUniqueField' + this.mappingId);
        combo.value = this.sfUniqueField;
        return combo;

    },
    getFieldForSfId: function () {
        let availableClasses = new Ext.data.JsonStore({
            fields: ['id', 'name'],
            proxy: {
                type: 'ajax',
                url: `/admin/pimcoresalesforce/default/pimfields/${this.pimcoreClassId}`,
                reader: {
                    type: 'json',
                    rootProperty: 'fields'
                }
            },
        });
        availableClasses.load();
        let combo = this.getCombo(availableClasses, 'psc_select_field_for_sf_id', 'fieldForSfId' + this.mappingId);
        combo.value = this.fieldForSfId;
        return combo;

    },

    getCombo: function (availableClasses, fieldLabel, name) {

        let combo = new Ext.create('Ext.form.ComboBox', {
            fieldLabel: t(fieldLabel) + ' <span style="color:red;">*</span>',
            name: name,
            id: name,
            style: {
                marginLeft: '30px'
            },
            store: availableClasses,
            queryMode: 'local',
            editable: true,
            allowBlank: false,
            blankText: t('psc_field_is_required'),
            msgTarget: 'under',
            displayField: 'name',
            valueField: 'id',
            width: 715,
            labelWidth: 220,

        });
        return combo;

    },
    getImportFilePathField: function () {

        var outerScope = this;
        let importFilePathField = new Ext.create('Ext.form.field.Text', {

            fieldLabel: t('psc_import_object_path'),
            name: 'importObjectPath',
            enableKeyEvents: true,
            fieldCls: "pimcore_droptarget_input",
            value: this.importFolderPath,
            editable: false,
            style: {
                marginLeft: '14px'
            },
            width: 715,
            labelWidth: 220,
            listeners: {
                "render": function (el) {
                    new Ext.dd.DropZone(el.getEl(), {
                        //reference: this,
                        ddGroup: "element",
                        getTargetFromEvent: function (e) {
                            return this.getEl();
                        }.bind(el),

                        onNodeOver: function (target, dd, e, data) {
                            if (data.records.length === 1 && data.records[0].data.elementType === "object" && data.records[0].data.type === "folder") {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            }
                        },
                        onNodeDrop: function (target, dd, e, data) {
                            if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                return false;
                            }
                            data = data.records[0].data;
                            if (data.elementType === "object" && data.type === "folder") {
                                this.setValue(data.path);
                                outerScope.importFolderId = data.id;
                                importFilePathRemoveButton.setHidden(false);
                                importFilePathOpenButton.setHidden(false);

                                return true;
                            }
                            return false;
                        }.bind(el)
                    });
                }
            }
        });

        let importFilePathOpenButton = Ext.create('Ext.Button', {
            // text: 'Open uploaded document',
            iconCls: "pimcore_material_icon_locate pimcore_material_icon",
            tooltip: t('dHub_show_in_tree'),
            hidden: !this.importFolderPath,
            style: {
                marginLeft: '20px'
            },
            handler: function (btn) {
                try {
                    console.log(outerScope.importFolderId)
                    pimcore.treenodelocator.showInTree(outerScope.importFolderId, "object")
                } catch (e) {
                    console.log(e);
                }
            }
        });

        let importFilePathRemoveButton = Ext.create('Ext.Button', {
            // text: 'Open uploaded document',
            iconCls: 'pimcore_icon_delete',
            hidden: !this.importFolderPath,
            tooltip: t('dHub_clear_value'),
            style: {
                marginLeft: '20px'
            },
            handler: function (btn) {
                importFilePathField.setValue(null);
                btn.setHidden(true);
                importFilePathOpenButton.setHidden(true);
            }
        });



        let exportFilePathFieldSet = new Ext.create('Ext.form.FieldSet', {
            layout: 'hbox',
            style: 'border-top: none !important',
            border: false,

            items: [importFilePathField, importFilePathRemoveButton, importFilePathOpenButton]
        });

        return exportFilePathFieldSet;

    }
}
);