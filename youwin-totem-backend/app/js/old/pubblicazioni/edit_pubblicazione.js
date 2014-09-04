Application.pubblicazioni.WinEdit = function(){

  this.init();  
};
Ext.extend(Application.pubblicazioni.WinEdit , Ext.util.Observable, {

  win:null,
  id:null,
  storeCategorie:null,
  storeMittenti:null,
  formPanel:null,
  comboCategorie:null,
  comboMittenti:null,
  dataInserimentoField:null,
  oggettoField:null,
  autoreField:null,
  pubDalField:null,
  pubAlField:null,
  nProtocolloField:null,
  annoProtocolloField:null,
  noteField:null,
  gridPanel:null,
  ordFileField:null,
  descriptionFileField:null,
  store:null,
  undo:false,
  
  init: function(){
    this.initStores();
    this.buildForm();
    this.buildGridPanel();
    this.addEvents({"updated" : true});    

    
    this.win = new Ext.Window({
        title: 'Aggiungi/Modifica Pubblicazione',
        width: 700,
        height: 600,
        layout: 'border',
        plain:true,
        modal:false,
        closable:false,
        iconCls: 'icon-shield',
        items:[this.formPanel,this.gridPanel],
        buttons: [{
          text: 'Salva',
          handler:function(){
        	this.saveForm();
          },
          scope:this
        },{
          text: 'Annulla',
          handler:function(){
        	if(this.undo==false){
        	 this.hide();
        	}
        	this.cancelNew();
          },
          scope:this
        }]
      });
      
      
  },
 
  show: function(id){
    this.id=id;
    if(this.id!='new'){
      this.loadForm(this.id);
      this.refreshGrid();
    }else{
      this.undo=true;
      this.createNew();
    }
    this.win.show();
  },
  
  loadForm:function(id){
    this.formPanel.getForm().load({
      url:'pubblicazioni/get',
      params:{
        id:id
      },
      scope:this,
      waitMsg:'Caricamento...'
    });
  },
  
  saveForm:function(){
    this.formPanel.getForm().submit({
	  clientValidation:false,
      url:'pubblicazioni/save',
      params:{
        id:this.id
      },
      waitMsg: 'Salvataggio in corso...',
      success: function(form,action){
        this.fireEvent('updated');
        this.hide();
      },
      failure: function(form,action){
        if (action.result.errorMessages){
          var errMsg=action.result.errorMessages.join("<br/>");
          Ext.MessageBox.show({
            title: 'Problema...',
            msg: errMsg,
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.WARNING,
            fn:function(btn){
              if (action.result.closeAfterErrors){
                this.hide();
              }
            },
            scope:this
          });
        }
      },
      scope:this
    });
  },

  hide: function(){
    Ext.QuickTips.init();
    this.fireEvent('updated');
    this.win.close();
  },
  
  buildForm:function(){
    this.dataInserimentoField=new Ext.form.DateField({
      name:'data_inserimento',
      fieldLabel:'Data creazione',
      allowBlank:false
    });
    this.oggettoField=new Ext.form.TextField({
      name:'oggetto',
      fieldLabel:'Oggetto'
    });
    this.autoreField=new Ext.form.TextField({
      name:'autore',
      fieldLabel:'Autore',
      allowBlank:false
    });
    this.pubDalField=new Ext.form.DateField({
      name:'pubblicato_dal',
      fieldLabel:'Pubblicata dal'
    });
    this.pubAlField=new Ext.form.DateField({
      name:'pubblicato_al',
      fieldLabel:'Pubblicata al'
    });
    this.nProtocolloField=new Ext.form.TextField({
      name:'n_protocollo',
      fieldLabel:'Numero protocollo',
      allowBlank:false
    });
    this.annoProtocolloField=new Ext.form.NumberField({
      name:'anno_protocollo',
      fieldLabel:'Anno protocollo',
      allowBlank:false
    });
    this.noteField=new Ext.form.TextArea({
      name:'note',
      fieldLabel:'Note'
    });
    this.comboCategorie=new Ext.form.ComboBox({
      fieldLabel:'Categoria',
      store:this.storeCategorie,
      triggerAction:'all',
      forceSelection:true,
      minChars :2,
      displayField:'denominazione',
      valueField:'ID_categoria',
      hiddenName:'ID_categoria'
    });
    
    this.comboMittenti=new Ext.form.ComboBox({
        fieldLabel:'Mittente',
        store:this.storeMittenti,
        triggerAction:'all',
        forceSelection:true,
        minChars :2,
        displayField:'denominazione',
        valueField:'ID_mittente',
        hiddenName:'ID_mittente'
      });
    
    
    this.formPanel= new Ext.FormPanel({
      baseCls: 'x-plain',
      region:'center',
      bodyStyle: 'padding: 10px 10px 0 10px;',
      labelWidth: 160,
      defaults: {anchor:'90%',msgTarget:'side'},
      items:[
        this.dataInserimentoField,
        this.oggettoField,
        this.comboCategorie,
        this.comboMittenti,
        this.autoreField,
        this.pubDalField,
        this.pubAlField,
        this.nProtocolloField,
        this.annoProtocolloField,
        this.noteField
      ]
    });
  },
  
  buildGridPanel:function(){
	this.rowAction=new Ext.ux.grid.RowActions({
	  actions:[{
	    iconCls:'icon-download',
	    qtip:'scarica'
	  }],
	  widthIntercept:Ext.isSafari ? 4 : 2,
	  id:'actions'
	});
	this.rowAction.on('action',function(grid, record, action, row, col){
	  switch(action) {
	    case 'icon-download':
	      this.download(record.id);
	    break;
	  }
	},this);
	
    this.orderFileField=new Ext.form.NumberField({
      allowBlank: false,
      selectOnFocus: true,
      allowDecimals: false
    });
    this.descriptionFileField=new Ext.form.TextField({
      allowBlank:false,
      selectOnFocus:true
    });
    this.store=new Ext.data.JsonStore({
      proxy: new Ext.data.HttpProxy({url:'pubblicazioni/listfiles'}),
      root:'results',
      id:'ID_file',
      fields:[
        {name:'ID_file',type:'int'},
        {name:'orgid',type:'int'},
        {name:'ID_pubblicazione',type:'int'},
        {name:'order_id',type:'int'},
        {name:'mime_type'},
        {name:'description'},
        {name:'file_name'},
        {name:'file_size',type:'int'}
      ]
    });
    /** nell'editorGridPanel 
     * bisogna sempre definire il selection model su ROW altrimenti di default utilizza 
     * il CellSelectionModel e in tal caso il metodo getSelected non esiste
    **/
    this.gridPanel=new Ext.grid.EditorGridPanel({
      region:'south',
      title:'Files allegati',
      border:true,
      height:200,
      store:this.store,
      clicksToEdit:1,
      sm:new Ext.grid.RowSelectionModel({
    	  singleSelect:true
      }),
      plugins:[this.rowAction],
      columns:[
        { header:'Tipo', width:140, dataIndex:'mime_type'},
        { header:'Ordine', width:60,css:'background:#CFCFCF;', dataIndex:'order_id',editor:this.orderFileField,tooltip:'Click sulla cella per editare'},
        { header:'Descrizione', css:'background:#CFCFCF;',width:140, dataIndex:'description',editor:this.descriptionFileField,tooltip:'Click sulla cella per editare'},
        { header:'Nome file', width:140, dataIndex:'file_name'},
        { header:'Size',width:80,dataIndex:'file_size'},
        this.rowAction
      ],
      tbar:[{
        text:'Aggiungi',
        icon:'css/icons/add_disk.png',
        iconCls:'x-btn-text-icon',
        tooltip:{title:'Aggiungi file',text:'Aggiunge uno o più files'},
        handler: function(){
          this.editFile('new');
        },
        scope:this
      },'-',{
        text:'Elimina',
        icon:'css/icons/delete_disk.png',
        iconCls:'x-btn-text-icon',
        tooltip:{title:'Elimina',text:'Elimina il file selezionato'},
        handler: function(){
        	console.log(this.gridPanel.getSelectionModel());
          if(!this.gridPanel.getSelectionModel().getSelected()){
            Ext.Msg.alert("Attenzione","Selezionare un file");
            return;
          }
          this.deleteFile(this.gridPanel.getSelectionModel().getSelected().data.ID_file);
        },
        scope:this
      }]
    });
    this.gridPanel.getStore().on('update',function(store,record,operation){
      if (operation==Ext.data.Record.EDIT){
       var params={id:record.id};
       if ('order_id' in record.modified)  params.order_id=record.data.order_id;
       if ('description' in record.modified)   params.description=record.data.description;
       Ext.Ajax.request({
         url:'pubblicazioni/fileedit',
         params:params,
         success:function(response,options){
           Utils.utils.msg("Avviso","Modifica eseguita correttamente.");
           store.commitChanges();
         },
         scope:this
       });
      }else{
        store.reload();
      }
    },this);
  },
  
  refreshGrid:function(){
    this.store.load({
      params:{
        id:this.id
      }
    });
  },
  
  editFile:function(){
    var editWin=new Application.pubblicazioni.WinUpload();
    editWin.show(this.id);
    editWin.on('updated',function(){this.refreshGrid();},this);
  },
  
  createNew:function(){
    Ext.Ajax.request({
      url:'pubblicazioni/create',
      success:function(response,options){
        var resp=Ext.decode(response.responseText);
        if (resp.success){
          this.id=resp.newid;
          this.loadForm(this.id);
        }else{
          Ext.MessageBox.show({
             title: 'Problema...',
             msg: resp.msg,
             buttons: Ext.MessageBox.OK,
             icon: Ext.MessageBox.WARNING
          });
        }
      },
      scope:this
    });
  },
  
  cancelNew:function(){
	/** prima controllare che non vi siano files allegati*/
    if(this.undo){
	  Ext.Ajax.request({
	    url:'pubblicazioni/undocreate',
	    params:{
	      id:this.id
	    },
	    success:function(response,options){
	      var result=Ext.decode(response.responseText);
	      if(result.success==true){
	    	this.hide();
	      }
	    },
	    scope:this
	  });
    }
  },
  
  deleteFile:function(ID_file){
	Ext.Msg.show({
	  title:'Eliminazione ?',
	  msg:'Eliminare il file selezionato da questa pubblicazione?',
	  buttons: Ext.Msg.YESNO,
	  fn:function(btn){
	    if (btn=='yes'){
	      Ext.Ajax.request({
	        url:'pubblicazioni/deletefile',
	        params:{
	    	  idFile:ID_file,
	    	  idPubblicazione:this.id
	    	},
	        success:function(response,options){
	          var result=Ext.decode(response.responseText);
	          if (result.success==true){
	            this.refreshGrid();
	          }else{
			    if (result.errorMessages){
			      var errMsg=result.errorMessages.join("<br/>");
			      Ext.MessageBox.show({
			        title: 'Problema...',
				    msg: errMsg,
				    buttons: Ext.MessageBox.OK,
				    icon: Ext.MessageBox.WARNING
				  });
			    }
	          }
	        },
	        scope:this
	      });
	    }
	  },
	  icon: Ext.MessageBox.QUESTION,
	  scope:this
	});  
  },
  
  initStores:function(){
    this.storeCategorie=new Ext.data.Store({
      proxy: new Ext.data.HttpProxy({url: 'categorie/list'}),
      reader:new Ext.data.JsonReader({
        root:'results',
        id:'ID_categoria'
      },[
        {name:'ID_categoria',type:'int'},
        {name:'orgid',type:'int'},
        {name:'denominazione',type:'string'}
      ])
    });
    this.storeCategorie.load();
    this.storeMittenti=new Ext.data.Store({
      proxy: new Ext.data.HttpProxy({url: 'mittenti/list'}),
      reader:new Ext.data.JsonReader({
        root:'results',
        id:'ID_mittente'
      },[
        {name:'ID_mittente',type:'int'},
        {name:'orgid',type:'int'},
        {name:'denominazione',type:'string'}
      ])
    });
    this.storeMittenti.load();
  },
  
  download:function(ID_file){
	Utils.utils.openWindow('Download file','pubblicazioni/downloadfile/id/'+ID_file+'/ID_pubblicazione/'+this.id);
  }
});