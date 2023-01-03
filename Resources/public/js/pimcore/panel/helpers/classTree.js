pimcore.registerNS("pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.helpers.classTree");
pimcore.plugin.SyncrasyPimcoreSalesforceBundle.panel.helpers.classTree = Class.create({

    showFieldName: false,
    notShow : ['isDummy','productId'],

    initialize: function (showFieldName) {
        if (showFieldName) {
            this.showFieldName = showFieldName;
        }
    },

    updateFilter: function (tree, filterField) {

        tree.getStore().clearFilter();
        var currentFilterValue = filterField.getValue().toLowerCase();

        tree.getStore().filterBy(function (item) {
            if (item.data.text.toLowerCase().indexOf(currentFilterValue) !== -1) {
                return true;
            }

            if (!item.data.leaf) {
                if (item.data.root) {
                    return true;
                }

                var childNodes = item.childNodes;
                var hide = true;
                if (childNodes) {
                    var i;
                    for (i = 0; i < childNodes.length; i++) {
                        var childNode = childNodes[i];
                        if (childNode.get("visible")) {
                            hide = false;
                            break;
                        }
                    }
                }

                return !hide;
            }
        }.bind(this));

        var rootNode = tree.getRootNode()
        rootNode.set('text', currentFilterValue ? t('element_tag_filtered_tags') : t('element_tag_all_tags'));
        //rootNode.expand(true);
    },

    getClassTree: function (url, classId, objectId) {

        var filterField = new Ext.form.field.Text(
            {
                width: 230,
                hideLabel: true,
                enableKeyEvents: true
            }
        );

        var filterButton = new Ext.button.Button({
            iconCls: "pimcore_icon_search"
        });

        var headerConfig = {
            title: t('class_attributes'),
            items: [
                filterField,
                filterButton
            ]
        };

        var tree = new Ext.tree.TreePanel({
            
            //tbar: headerConfig,
            region: "center",
            autoScroll: true,
            rootVisible: false,
            bufferedRenderer: false,
            animate: false,
            expanded: false,
            width: 500,
            root: {
                id: "0",
                root: true,
                text: t("base"),
                allowDrag: false,
                leaf: true,
                isTarget: true
            },
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    enableDrag: true,
                    enableDrop: false,
                    ddGroup: "columnconfigelement"
                }
            },
            listeners: {
                itemexpand: function ( node, eOpts  ){
                    if(node.data.type == 'classificationstore_store' && node.childNodes.length == 0) {
                        var loadCollections = Ext.MessageBox.show({title: 'Loading', msg: 'Please wait loading collections'});
                        Ext.Ajax.request({
                            url: "/admin/pimcoresalesforce/default/getCollections",
                            method: 'POST',
                            success: function(response){
                                result = Ext.decode(response.responseText);
                                $.each(result, function(index, collection){
                                    var newNode = {
                                        type: "classification_collection",
                                        expanded: false,
                                        expandable: true,
                                        allowDrag: false,
                                        iconCls: "pimcore_icon_panel",
                                        text: collection.title,
                                        id: "collection_"+collection.id
                                    };

                                    newNode = node.appendChild(newNode);
                                })
                                loadCollections.hide()
                            },
                            failure: function(response){
                                Ext.MessageBox.hide()
                                alert("Error in collection loading" );


                            }
                        });

                    }
                    if(node.data.type == 'classification_collection' && node.childNodes.length == 0) {
                        //console.log(node)
                        Ext.Ajax.request({
                            url: "/admin/pimcoresalesforce/default/getCollectionGroups",

                            method: 'POST',
                            params: {
                                collectionId: node.data.id
                            },
                            success: function(response){
                                result = Ext.decode(response.responseText);
                                $.each(result, function(index, group){
                                    var newNode = {
                                        type: "classification_group",
                                        expanded: false,
                                        expandable: true,
                                        allowDrag: false,
                                        iconCls: "pimcore_icon_panel",
                                        text: group.title,
                                        id: "group_"+group.id
                                    };

                                    newNode = node.appendChild(newNode);
                                })
                            },
                            failure: function(response){
                                alert("Error in collection " );

                            }
                        });
                    }
                    if(node.data.type == 'classification_group' && node.childNodes.length == 0) {
                        Ext.Ajax.request({
                            url: "/admin/pimcoresalesforce/default/getGroupKeys",

                            method: 'POST',
                            params: {
                                groupId: node.data.id
                            },
                            success: function(response){
                                result = Ext.decode(response.responseText);

                                var brickDescriptor = {};
                                $.each(result, function(index, keyObj){
                                    var newNode = {
                                        text: keyObj.title,
                                        key: keyObj.name,
                                        type: "data",
                                        layout: keyObj,
                                        leaf: true,
                                        allowDrag: true,
                                        dataType: keyObj.fieldtype,
                                        iconCls: "pimcore_icon_" + keyObj.fieldtype,
                                        expanded: true,
                                        brickDescriptor: brickDescriptor
                                    };
                                    newNode = node.appendChild(newNode);

                                })
                            },
                            failure: function(response){
                                alert("Error in collection " );

                            }
                        });
                    }
                }.bind(this)
            }

        });

        Ext.Ajax.request({
            url: url,
            params: {
                id: classId,
                oid: objectId
            },
            success: this.initLayoutFields.bind(this, tree)
        });

        filterField.on("keyup", this.updateFilter.bind(this, tree, filterField));
        filterButton.on("click", this.updateFilter.bind(this, tree, filterField));

        return tree;
    },

    initLayoutFields: function (tree, response) {
        var data = Ext.decode(response.responseText);

        var keys = Object.keys(data);
        for (var i = 0; i < keys.length; i++) {
            if (data[keys[i]]) {
                if (data[keys[i]].childs) {

                    var text = t(data[keys[i]].nodeLabel);

                    var brickDescriptor = {};

                    if (data[keys[i]].nodeType == "objectbricks") {
                        brickDescriptor = {
                            insideBrick: true,
                            brickType: data[keys[i]].nodeLabel,
                            brickField: data[keys[i]].brickField
                        };

                        text = ts(data[keys[i]].nodeLabel) + " " + t("columns");

                    }
                    if(text != 'Taxanomy') {
                        var baseNode = {
                            type: "layout",
                            allowDrag: false,
                            iconCls: "pimcore_icon_" + data[keys[i]].nodeType,
                            parentType : data[keys[i]].parentType ?  data[keys[i]].parentType : '',
                            text: text
                        };

                        baseNode = tree.getRootNode().appendChild(baseNode);
                    
                        for (var j = 0; j < data[keys[i]].childs.length; j++) {
                            baseNode.appendChild(this.recursiveAddNode(data[keys[i]].childs[j], baseNode, brickDescriptor));
                        }
                         
                    }
                }
            }
        }
    },

    recursiveAddNode: function (con, scope, brickDescriptor) {

        var fn = null;
        var newNode = null;
        if (con.datatype == "layout") {
            fn = this.addLayoutChild.bind(scope, con.fieldtype, con);
        }
        else if (con.datatype == "data") {
            fn = this.addDataChild.bind(scope, con.fieldtype, con, this.showFieldName, brickDescriptor);
        }

        newNode = fn();

        if (con.childs) {
            for (var i = 0; i < con.childs.length; i++) {
                this.recursiveAddNode(con.childs[i], newNode, brickDescriptor);
            }
        }

        return newNode;
    },

    addLayoutChild: function (type, initData) {

        var nodeLabel = t(type); 
        if (initData) {
            if (initData.title) {
                nodeLabel = initData.title;
            } else if (initData.name) {
                nodeLabel = initData.name;
            }
        }

        if(type != 'classificationstore') {
            expandable = initData.childs.length
            expanded = true,
                datatype = 'layout'
        } else {
            expandable = 1
            expanded = false
            datatype = 'classificationstore_store'
        }

        var newNode = {
            // type: "layout",
            type: datatype,
            expanded: expanded,
            expandable: expandable,
            allowDrag: false,
            iconCls: "pimcore_icon_" + type,
            text: nodeLabel
        };

        newNode = this.appendChild(newNode);

        this.expand();

        return newNode;
    },

    addDataChild: function (type, initData, showFieldname, brickDescriptor) {
        // && !initData.invisible
        var notShow = ['isDummy','productId','keywords','manufacturer'];

        if (type != "objectbricks" && notShow.indexOf(initData.name) == -1) {
            var isLeaf = true;
            var draggable = true;

            // localizedfields can be a drop target
            if (type == "localizedfields") {

                isLeaf = false;
                draggable = false;

                Ext.apply(brickDescriptor, {
                    insideLocalizedFields: true
                });

            }

            var key = initData.name;

            if (brickDescriptor && brickDescriptor.insideBrick) {
                if (brickDescriptor.insideLocalizedFields) {
                    var parts = {
                        containerKey: brickDescriptor.brickType,
                        fieldname: brickDescriptor.brickField,
                        brickfield: key
                    }
                    key = "?" + Ext.encode(parts) + "~" + key;
                } else {
                    key = brickDescriptor.brickType + "~" + key;
                }
            }

            var text = ts(initData.title);
            if (showFieldname) {
                text = t(text);
            }
            var newNode = {
                text: text,
                key: key,
                type: "data",
                layout: initData,
                leaf: isLeaf,
                allowDrag: draggable,
                dataType: type,
                iconCls: "pimcore_icon_" + type,
                expanded: true,
                brickDescriptor: brickDescriptor
            };

            newNode = this.appendChild(newNode);

            if (this.rendered) {
                this.expand();
            }

            return newNode;
        } else {
            return null;
        }

    }

});

