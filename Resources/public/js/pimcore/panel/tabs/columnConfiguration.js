pimcore.registerNS("pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.tabs.columnConfiguration");
pimcore.object.gridcolumn.operator.ignore = pimcore.object.importcolumn.operator.ignore;
pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.tabs.columnConfiguration = Class.create(pimcore.element.abstract, {
    initialize: function (config, parentType) {
        this.mappingAttributeSetting = config.columnAttributeMapping; 
        this.lang = config.data.lang;
        this.config = this.mappingAttributeSetting;
        this.mappingPimId = config.general.o_id;
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
            iconCls: "psc_column_mapping",
            title: t("psc_column_configuration"),
            buttons: {
                itemId: 'distributionhub_column_configuration_footer'
            },
            items: []
        });

        this.rebuildPanel();
        this.getConfigButton();
    },
    buildDefaultSelection: function () {
        this.config.selectedGridColumns = [];
        console.log(pimcore.object.gridcolumn.operator);
        var ignoreImpl = pimcore.object.gridcolumn.operator.ignore.prototype;
        if (this.config.dataFields) {
            for (var i = 0; i < this.config.dataFields.length; i++) {
                this.config.selectedGridColumns.push({
                    isOperator: true,
                    targetAttributeKey: '',
                    targetAttributeId: '',
                    sourceDataType: '',
                    attributes: {
                        type: ignoreImpl.type,
                        class: ignoreImpl.class,
                        isOperator: true
                    }
                });
            }
        }
    },
    rebuildPanel: function () {
        this.configPanel.removeAll(true);
        this.selectionPanel = null;
        this.leftPanel = null;
        this.classDefinitionTreePanel = null;
        this.configPanel.add(this.getSelectionPanel());
        this.configPanel.add(this.getLeftPanel());
    },
    reloadPanel: function (responseData) {
        this.config = responseData;
        this.buildDefaultSelection();
        this.configPanel.removeAll(true);
        this.selectionPanel = null;
        this.leftPanel = null;
        this.classDefinitionTreePanel = null;
        this.configPanel.add(this.getSelectionPanel());
        this.configPanel.add(this.getLeftPanel());
    },
    getConfigButton: function () {
        var footer = this.configPanel.getDockedComponent('distributionhub_column_configuration_footer');
        footer.removeAll();
        footer.add({
            text: t("psc_reset_mapping"),
            iconCls: "pimcore_icon_refresh",
            handler: this.rebuildPanel.bind(this)
        });
        footer.add({
            text: t("psc_import_header"),
            iconCls: "pimcore_icon_start_import",
            handler: this.importConfigHeaders.bind(this, this.mappingPimId)
        });
        footer.add({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.saveChannelMappingConfig.bind(this)
        });
    },
    importConfigHeaders: function () {
        let scope = this;
        let loadMsg = Ext.MessageBox.wait(t('psc_read_column'));
        Ext.Ajax.request({
            url: "/admin/pimcoresalesforce/default/mapping-header-import",
            waitMsg: 'Reading Mapping ....',
            params: {
                id: this.mappingPimId,
                csrfToken: pimcore.settings.csrfToken,
            },
            method: 'POST',
            success: function (response, action) {
                let responseData = Ext.decode(response.responseText);
                if (responseData.success) {
                    console.log('config----',responseData.config);
                    Ext.apply(this.config, {});
                    Ext.apply(this.config, responseData.config);
                    scope.reloadPanel(responseData.config);
                } else {
                    pimcore.helpers.showNotification(t("error"), t(responseData.message), 'error');
                }
                loadMsg.hide();
            },
            failure: function (response, action) {
                loadMsg.hide();
                var responseData = Ext.decode(response.responseText);
                var msg = (responseData.message && responseData.message != "") ? responseData.message : "psc_error_reading_columns";
                pimcore.helpers.showNotification(t("error"), t(msg), 'error');
            }
        });
    },
    getPanel: function () {
        return this.configPanel;
    },
    getLeftPanel: function () {
        if (!this.leftPanel) {
            let items = this.getOperatorTrees();
            items.unshift(this.getClassDefinitionTreePanel());
            this.brickKeys = [];
            this.leftPanel = new Ext.Panel({
                cls: "pimcore_panel_tree pimcore_gridconfig_leftpanel",
                region: "center",
                split: true,
                width: 200,
                minSize: 125,
                collapsible: true,
                collapseMode: 'header',
                collapsed: false,
                animCollapse: false,
                layout: 'accordion',
                hideCollapseTool: true,
                header: false,
                layoutConfig: {
                    animate: false
                },
                hideMode: "offsets",
                items: items
            });
        }
        return this.leftPanel;
    },
    getOperatorTrees: function () {
        var operators = Object.keys(pimcore.object.gridcolumn.operator);
        var operatorGroups = [];
        for (var i = 0; i < operators.length; i++) {
            var operator = operators[i];
            if (!this.availableOperators || this.availableOperators.indexOf(operator) >= 0) {
                var nodeConfig = pimcore.object.gridcolumn.operator[operator].prototype;
                if (this.allowedOperatorList.indexOf(nodeConfig.class) > -1) {
                    var configTreeNode = nodeConfig.getConfigTreeNode();

                    var operatorGroup = nodeConfig.operatorGroup ? nodeConfig.operatorGroup : "other";

                    if (!operatorGroups[operatorGroup]) {
                        operatorGroups[operatorGroup] = [];
                    }

                    var groupName = nodeConfig.group || "other";
                    if (!operatorGroups[operatorGroup][groupName]) {
                        operatorGroups[operatorGroup][groupName] = [];
                    }
                    operatorGroups[operatorGroup][groupName].push(configTreeNode);
                }
            }
        }
        var operatorGroupKeys = [];
        for (k in operatorGroups) {
            if (operatorGroups.hasOwnProperty(k)) {
                operatorGroupKeys.push(k);
            }
        }
        operatorGroupKeys.sort();
        var result = [];
        var len = operatorGroupKeys.length;
        for (i = 0; i < len; i++) {
            var operatorGroupName = operatorGroupKeys[i];
            var groupNodes = operatorGroups[operatorGroupName];
            result.push(this.getOperatorTree(operatorGroupName, groupNodes));

        }
        return result;
    },
    getOperatorTree: function (operatorGroupName, groups) {
        var groupKeys = [];
        for (k in groups) {
            if (groups.hasOwnProperty(k)) {
                groupKeys.push(k);
            }
        }
        groupKeys.sort();
        var len = groupKeys.length;

        var groupNodes = [];

        for (i = 0; i < len; i++) {
            var k = groupKeys[i];
            var childs = groups[k];
            childs.sort(
                function (x, y) {
                    return x.text < y.text ? -1 : 1;
                }
            );

            var groupNode = {
                iconCls: 'pimcore_icon_folder',
                text: t(k),
                allowDrag: false,
                allowDrop: false,
                leaf: false,
                expanded: true,
                children: childs
            };

            groupNodes.push(groupNode);
        }

        var tree = new Ext.tree.TreePanel({
            title: t('operator_group_' + operatorGroupName),
            iconCls: 'pimcore_icon_gridconfig_operator_' + operatorGroupName,
            xtype: "treepanel",
            region: "south",
            autoScroll: true,
            layout: 'fit',
            rootVisible: false,
            resizeable: true,
            split: true,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    ddGroup: "columnconfigelement",
                    enableDrag: true,
                    enableDrop: false
                }
            },
            root: {
                id: "0",
                root: true,
                text: t("base"),
                draggable: false,
                leaf: false,
                isTarget: false,
                children: groupNodes
            }
        });

        return tree;
    },
    doBuildChannelConfigTree: function (configuration) {
        var elements = [];
        if (configuration) {
            for (var i = 0; i < configuration.length; i++) {
                var configElement = this.getConfigElement(configuration[i]);
                if (configElement) {
                    var treenode = configElement.getConfigTreeNode(configuration[i]);
                    var nodeConf = configuration[i];

                    treenode.draggable = false;
                    treenode.targetAttributeKey = nodeConf.targetAttributeKey;
                    treenode.targetAttributeId = nodeConf.targetAttributeId;
                    treenode.sourceDataType = nodeConf.sourceDataType;
                    treenode.isLocalizedfield = nodeConf.isLocalizedfield;
                    if (configuration[i].childs) {
                        var childs = this.doBuildChannelConfigTree(configuration[i].childs);
                        treenode.children = childs;
                        if (childs.length > 0) {
                            treenode.expandable = true;
                        }
                    }
                    elements.push(treenode);
                }
            }
        }
        return elements;
    },
    getSelectionPanel: function () {
        var scope = this;
        if (!this.selectionPanel) {
            var childs = [];
            for (var i = 0; i < this.config.selectedGridColumns.length; i++) {
                var nodeConf = this.config.selectedGridColumns[i];
                if (typeof nodeConf.isOperator == "undefined") {
                    nodeConf.isValue = true;
                }
                if (nodeConf.isOperator || nodeConf.isValue) {
                    var child = this.doBuildChannelConfigTree([nodeConf.attributes]);
                    if (!child || !child[0]) {
                        continue;
                    }
                    child = child[0];

                    child.key = nodeConf.key;
                    child.draggable = false;
                    child.allowDrop = true;
                    child.targetAttributeKey = nodeConf.targetAttributeKey;
                    child.targetAttributeId = nodeConf.targetAttributeId;
                    child.sourceDataType = nodeConf.sourceDataType;
                    child.isLocalizedfield = nodeConf.isLocalizedfield;

                }
                childs.push(child);
            }
            //Language combo 
            var availableLanguage = new Ext.data.JsonStore({
                proxy: {
                    type: 'ajax',
                    url: '/admin/pimcoresalesforce/default/get-available-languages',
                    reader: {
                        type: 'json',
                        rootProperty: 'languages'
                    }
                },
                fields: ["name", "value"]
            });

            availableLanguage.load();

            this.langSelection = Ext.create('Ext.form.ComboBox', {
                fieldLabel: 'Select Language <span style="color:red;">*</span>',
                style: "padding-left: 10px",
                labelWidth: 140,
                autoSelect: true, 
                allowBlank: false,
                blankText: t('psc_field_is_required'),
                msgTarget: 'under', 
                store: availableLanguage,
                queryMode: 'local',
                editable: false,
                displayField: 'name',
                valueField: 'value',
                width: 300,
                value: this.languageComboValue
            });
            
            if(this.lang){
                this.langSelection.setValue(this.lang);
            }
            
            this.selectionPanel = new Ext.tree.TreePanel({
                root: {
                    id: "0",
                    root: true,
                    text: t("psc_attribute_mapping"),
                    leaf: false,
                    isTarget: true,
                    expanded: true,
                    allowDrop: false,
                    children: childs
                },
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [
                        this.langSelection
                    ]
                }],
                columns: [{
                        xtype: 'treecolumn', //this is so we know which column will show the tree
                        text: t('psc_source'),
                        dataIndex: 'text',
                        flex: 2,
                        sortable: true,
                        renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (record.data && record.data.configAttributes && record.data.configAttributes.class == "Ignore") {
                                metaData.tdCls += ' pimcore_import_operator_ignore';
                            }

                            return value;
                        }
                    },
                    {
                        text: t('col_idx'),
                        dataIndex: 'indx',
                        width: 70,
                        sortable: true
                    },
                    {
                        text: t('psc_destination'),
                        dataIndex: 'salesforcefieldApi',
                        width: 100,
                        flex: 2,
                        sortable: true
                    },
                ],
                viewConfig: {
                    plugins: {
                        ptype: 'treeviewdragdrop',
                        ddGroup: "columnconfigelement"
                    },
                    listeners: {
                        beforedrop: function (node, data, overModel, dropPosition, dropHandlers, eOpts) {
                            if (dropPosition != 'append') {
                                return false;
                            }
                            var target = overModel.getOwnerTree().getView();
                            var source = data.view;

                            if (target != source) {
                                var record = data.records[0];
                                var realOverModel = overModel;
                                var isOverwrite = false;
                                if (dropPosition == "before" || dropPosition == "after") {
                                    realOverModel = overModel.parentNode;
                                } else {
                                    if (typeof realOverModel.data.isOverwriteAllowed == "function") {
                                        if (realOverModel.data.isOverwriteAllowed(realOverModel, record)) {
                                            isOverwrite = true;
                                        }
                                    }
                                }
                                var attr = record.data;
                                if (record.data.configAttributes) {
                                    attr = record.data.configAttributes;
                                }
                                var element = this.getConfigElement(attr);
 
                                var copy = element.getCopyNode(record);
                                copy.data.allowDrop = false;
                                copy.data.allowDrop = record.data.isOperator;

                                if (isOverwrite) {
                                    if(!copy.hasOwnProperty('key')){
                                        if(record.data.isOperator){
                                            copy.data.key = "#"+ ( Math.floor(Math.random() * 10000000000) );
                                        }else{
                                            copy.data.key = copy.data.configAttributes.attribute;
                                        }
                                    }
                                    var parentNode = realOverModel.parentNode;
                                    parentNode.replaceChild(copy, realOverModel);
                                    dropHandlers.cancelDrop();
                                    this.updatePreviewArea();
                                } else {
                                    data.records = [copy]; // assign the copy as the new dropNode
                                }
                                this.showConfigWindow(element, copy);
                            } else {
                                // node has been moved inside right selection panel
                                var record = data.records[0];
                                var isOperator = record.data.isOperator;
                                var realOverModel = overModel;
                                if (dropPosition == "before" || dropPosition == "after") {
                                    realOverModel = overModel.parentNode;
                                }

                                if (isOperator || this.parentIsOperator(realOverModel)) {
                                    var attr = record.data;
                                    if (record.data.configAttributes) {
                                        // there is nothing to do, this guy has been configured already
                                        return;
                                    }
                                    var element = this.getConfigElement(attr);
                                    var copy = element.getCopyNode(record);

                                    data.records = [copy]; // assign the copy as the new dropNode
                                    this.showConfigWindow(element, copy);
                                    record.parentNode.removeChild(record);

                                }
                            }
                        }.bind(this),
                        drop: function (node, data, overModel) {

                            var record = data.records[0];
                            record.set("salesforcefieldApi", null, {
                                dirty: false
                            });

                            record.set("indx", null, {
                                dirty: false
                            });

                            this.updatePreviewArea();

                        }.bind(this),
                        nodedragover: function (targetNode, dropPosition, dragData, e, eOpts) {
                            var sourceNode = dragData.records[0];
                            var realOverModel = targetNode;
                            if (!sourceNode.data.isOperator && (!sourceNode.data.allowDrag || !sourceNode.data.allowDrop)) {
                                return false;
                            }

                            if (dropPosition == "before" || dropPosition == "after") {
                                realOverModel = realOverModel.parentNode;
                            } else {
                                // special handling for replacing nodes
                                if (typeof realOverModel.data.isOverwriteAllowed == "function") {
                                    if (realOverModel.data.isOverwriteAllowed(realOverModel, sourceNode)) {
                                        return true;
                                    }
                                }
                            }

                            var allowed = true;

                            if (typeof realOverModel.data.isChildAllowed == "function") {
                                allowed = allowed && realOverModel.data.isChildAllowed(realOverModel, sourceNode);
                            }

                            if (typeof sourceNode.data.isParentAllowed == "function") {
                                allowed = allowed && sourceNode.data.isParentAllowed(realOverModel, sourceNode);
                            }
                            return allowed;

                        }.bind(this),
                        options: {
                            target: this.selectionPanel
                        }
                    }
                },
                region: 'east',
                title: t('psc_attribute_mapping'),
                iconCls: 'pimcore_icon_operator_concatenator',
                layout: 'fit',
                width: 650,
                split: true,
                autoScroll: true,
                listeners: {
                    itemcontextmenu: this.onTreeNodeContextmenu.bind(this)
                }
            });


            var store = this.selectionPanel.getStore();
            var model = store.getModel();
            model.setProxy({
                type: 'memory'
            });
        }

        this.updatePreviewArea();
        return this.selectionPanel;
    },
    showConfigWindow: function (element, node) {
        var window = element.getConfigDialog(node);

        if (window) {
            //this is needed because of new focus management of extjs6
            setTimeout(function () {
                window.focus();
            }, 250);
        }
    },
    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        e.stopEvent();

        tree.select();

        var rootNode = tree.getStore().getRootNode();

        var menu = new Ext.menu.Menu();

        if (this.id != 0) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "pimcore_icon_delete",
                handler: function (record) {
                    let replacement = pimcore.object.gridcolumn.operator.ignore.prototype.getCopyNode(record);
                    replacement.data.configAttributes.label = "Operator Ignore";
                    replacement.data.text = "Operator Ignore";
                    if (record.data.depth > 1 || record.data.csvLabel == "") {
                        record.parentNode.removeChild(record, true);
                    } else {
                        record.parentNode.replaceChild(replacement, record);
                    }
                    this.updatePreviewArea();
                }.bind(this, record)
            }));

            if (record.data.children && record.data.children.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('collapse_children'),
                    iconCls: "pimcore_icon_collapse_children",
                    handler: function (node) {
                        record.collapseChildren();
                    }.bind(this, record)
                }));

            }

            if (record.data.isOperator || record.data.isValue) {
                menu.add(new Ext.menu.Item({
                    text: t('edit'),
                    iconCls: "pimcore_icon_edit",
                    handler: function (node) {
                        this.getConfigElement(node.data.configAttributes).getConfigDialog(node);
                    }.bind(this, record)
                }));

                if (record.parentNode == rootNode) {
                    menu.add(new Ext.menu.Item({
                        text: t('ignore'),
                        iconCls: "pimcore_icon_operator_ignore",
                        handler: function (node) {
                            var replacement = pimcore.object.gridcolumn.operator.ignore.prototype.getCopyNode(node);
                            var parent = node.parentNode;
                            parent.replaceChild(replacement, node);
                            this.updatePreviewArea();
                        }.bind(this, record)
                    }));
                }

                menu.add(this.getChangeTypeMenu(record));
            }
        }

        menu.showAt(e.pageX, e.pageY);
    },
    getChangeTypeMenu: function (record) {
        var operators = Object.keys(pimcore.object.gridcolumn.operator);
        var childs = [];
        for (var i = 0; i < operators.length; i++) {
            let operatorName = operators[i];
            let operatorClass = pimcore.object.gridcolumn.operator[operatorName].prototype;
            if (this.allowedOperatorList.indexOf(operatorClass.class) > -1) {
                childs.push(pimcore.object.gridcolumn.operator[operators[i]].prototype.getConfigTreeNode());
            }
        }

        childs.sort(
            function (x, y) {
                return x.text < y.text ? -1 : 1;
            }
        );

        var menu = [];
        for (var i = 0; i < childs.length; i++) {
            var child = childs[i];
            var item = new Ext.menu.Item({
                text: child.text,
                iconCls: child.iconCls,
                hideOnClick: true,
                handler: function (node, newType) {
                    var jsClass = newType.toLowerCase();
                    var replacement = pimcore.object.gridcolumn.operator[jsClass].prototype.getConfigTreeNode();

                    replacement.expanded = node.data.expanded;
                    replacement.expandable = node.data.expandable;
                    replacement.leaf = node.data.leaf;

                    replacement = node.createNode(replacement);
                    replacement.data.configAttributes.label = node.data.configAttributes.label;
                    replacement.data.text = node.data.configAttributes.label;
                    var parent = node.parentNode;
                    var originalChilds = [];

                    node.eachChild(function (child) {
                        originalChilds.push(child);
                    });


                    node.removeAll();
                    parent.replaceChild(replacement, node);

                    replacement.appendChild(originalChilds);

                    var element = this.getConfigElement(replacement.data.configAttributes);
                    this.showConfigWindow(element, replacement);
                    this.updatePreviewArea();
                }.bind(this, record, child.configAttributes.class)
            });
            menu.push(item);
        }

        var changeTypeItem = new Ext.menu.Item({
            text: t('convert_to'),
            iconCls: "pimcore_icon_convert",
            hideOnClick: false,
            menu: menu
        });
        return changeTypeItem;

    },
    updatePreviewArea: function () {
        var rootNode = this.selectionPanel.getRootNode();

        //var dataApplyFilter = this.config.dataApplyFilter;
        var dataShowInGrid = this.config.showInGrid;
        var dataPreview = this.config.dataPreview;
        if (dataPreview) {
            dataPreview = dataPreview[0];
        }
       

        if (dataShowInGrid) {
            dataShowInGrid = dataShowInGrid[0];
        }
        var children = rootNode.childNodes;
        if (children && children.length > 0) {
            for (var i = 0; i < children.length; i++) {
                var c = children[i];
                c.set("indx", i, {
                    dirty: false
                });

                var preview = "";
                if (dataPreview && dataPreview["field_" + i]) {
                    preview = dataPreview["field_" + i];
                }
                c.set("salesforcefieldApi", preview, {
                    dirty: false
                });
            }
        }
    },
    getClassDefinitionTreePanel: function () {
        if (!this.classDefinitionTreePanel) {
            let outerScope = this;
            this.brickKeys = [];
            let tree = this.getClassTree("/admin/pimcoresalesforce/default/get-class-definition-for-column-config",
                this.config.classId, 0);
            if (typeof this.config.classId !== 'undefined' && this.config.classId !== 0 && this.config.classId !== null) {
                Ext.Ajax.request({
                    url: "/admin/pimcoresalesforce/default/get-classification-store-for-column-config",
                    method: "GET",
                    params: {
                        id: this.config.classId
                    },
                    success: function(response){
                       let result = Ext.decode(response.responseText);
                       if(result.length >0){
                           result.forEach(function (element, index, array) {
                               let classAttributeStore = new pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.helpers.classAttributesStore(element.name, index);
                               let storeAttribute= classAttributeStore.getClassificationStorePanel(element.name, element.storeId);
                               outerScope.classDefinitionTreePanel.add(storeAttribute);
                           });
                       }
                    },
                    failure: function(response){
                        alert("Error in classification store " );
                    }
                });
            }
            this.classDefinitionTreePanel = new Ext.Panel({
                title: t('class_attributes'),
                iconCls: 'pimcore_icon_gridconfig_class_attributes',
                region: "center",
                width: 150,
                autoScroll:true,
                rootVisible: false,
                items: []
            });
            this.classDefinitionTreePanel.add(tree);
        }
        return this.classDefinitionTreePanel;
    },

    getClassTree: function (url, classId) {

        const classTreeHelper = new pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.helpers.classTree(true);

        let tree = new Ext.tree.TreePanel({
            region: "center",
            rootVisible: false
        });
        if (typeof classId === 'undefined' || classId === 0 || classId === null) {
            return tree;
        } else {
            tree = classTreeHelper.getClassTree(url, classId);
        }
        return tree;
    },
    getConfigElement: function (configAttributes) {
        configAttributes.dataType = configAttributes.dataType ? configAttributes.dataType : 'input'
        var element = null;
        if (configAttributes && configAttributes.class && configAttributes.type) {
            var jsClass = configAttributes.class.toLowerCase();
            if (pimcore.object.gridcolumn[configAttributes.type] && pimcore.object.gridcolumn[configAttributes.type][jsClass]) {
                element = new pimcore.object.gridcolumn[configAttributes.type][jsClass](this.config.classId);
            }
        } else {
            var dataType = configAttributes.dataType ? configAttributes.dataType.toLowerCase() : null;
            if (dataType && pimcore.object.gridcolumn.value[dataType]) {
                element = new pimcore.object.gridcolumn.value[dataType](this.config.classId);
            } else {
                element = new pimcore.object.gridcolumn.value.defaultvalue(this.config.classId);
            }
        }
        return element;
    },
    doBuildChannelConfigTree: function (configuration) {
        var elements = [];
        if (configuration) {
            for (var i = 0; i < configuration.length; i++) {
                var configElement = this.getConfigElement(configuration[i]);
                if (configElement) {
                    var treenode = configElement.getConfigTreeNode(configuration[i]);
                    var nodeConf = configuration[i];

                    treenode.draggable = false;
                    treenode.targetAttributeKey = nodeConf.targetAttributeKey;
                    treenode.targetAttributeId = nodeConf.targetAttributeId;
                    treenode.sourceDataType = nodeConf.sourceDataType;
                    treenode.isLocalizedfield = nodeConf.isLocalizedfield;
                    if (configuration[i].childs) {
                        var childs = this.doBuildChannelConfigTree(configuration[i].childs);
                        treenode.children = childs;
                        if (childs.length > 0) {
                            treenode.expandable = true;
                        }
                    }
                    elements.push(treenode);
                }
            }
        }
        return elements;
    },
    saveChannelMappingConfig: function () { 
        if (typeof this.config.classId != "undefined" && !Ext.isEmpty(this.config.classId) && this.config.classId.length > 0 && this.langSelection.isValid()) {
            pimcore.helpers.loadingShow();
            this.commitData();
            let mappingJson = Ext.encode(this.config);
            console.log('----json------',mappingJson);
            let saveData = {
                mappingId: this.mappingPimId,
                mappingJson: mappingJson,
                lang: this.langSelection.value
            };
            Ext.Ajax.request({
                url: "/admin/pimcoresalesforce/default/save-basic-config",
                params: saveData,
                waitMsg: 'Saving Columns Config....',
                method: "post",
                success: function (response) {
                    var rdata = Ext.decode(response.responseText);
                    pimcore.helpers.loadingHide();
                    if (rdata && rdata.success) {
                        pimcore.helpers.showNotification(t("success"), t("psc_mapping_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("psc_mapping_saveerror"), "error", t(rdata.message));
                    }
                }.bind(this)
            });
        }
    },
    commitData: function () {
        this.config.selectedGridColumns = [];
        var operatorFound = false;
        if (this.selectionPanel) {
            let count = 0;
            this.selectionPanel.getRootNode().eachChild(function (child) {
                var obj = {};
                var attributes = child.data.configAttributes;
                var operatorChilds = this.doGetRecursiveData(child);
                attributes.childs = operatorChilds;
                operatorFound = true;

                obj.key = child.data.key;
                obj.isOperator = child.data.isOperator;
                obj.isValue = child.data.isValue;
                obj.attributes = attributes;
                this.config.selectedGridColumns.push(obj);
                count++

            }.bind(this));
        }
    },
    doGetRecursiveData: function (node) {
        var childs = [];
        node.eachChild(function (child) {
            var attributes = child.data.configAttributes;
            attributes.targetAttributeKey = child.data.targetAttributeKey ? child.data.targetAttributeKey : "";
            attributes.sourceDataType = child.data.sourceDataType ? child.data.sourceDataType : "";
            attributes.targetAttributeId = child.data.targetAttributeId ? child.data.targetAttributeId : "";
            attributes.isLocalizedfield = child.data.isLocalizedfield ? child.data.isLocalizedfield : false;
            attributes.childs = this.doGetRecursiveData(child);
            childs.push(attributes);
        }.bind(this));

        return childs;
    },

});
