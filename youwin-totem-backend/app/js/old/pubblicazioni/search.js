Application.pubblicazioni.searchMask = function(){
  this.init();
};
Ext.extend(Application.pubblicazioni.searchMask , Ext.util.Observable, {
  win:null,
  dataInserimentoField:null,
  oggettoField:null,
  comboCategorie:null,
  comboMittenti:null,
  autoreField:null,
  pubDalField:null,
  pubAlField:null,
  nProtocolloField:null,
  annoProtocolloField:null,
  nomeFile:null,
  storeCategorie:null,
  storeMittenti:null,
  formPanel:null,

  init: function(){
  
    this.buildForm();
    this.addEvents({"searchPubblicazione" : true});    
  
    this.win = new Ext.Window({
	  title: 'Ricerca pubblicazioni',
      iconCls: 'icon-shield',
	  width: 400,
	  height: 400,
	  plain:true,
	  modal:false,
	  border:false,
	  constrainHeader:true,
	  shim:false,
	  animCollapse:false,
	  buttonAlign:'right',
	  maximizable:false,
	  items:[this.formPanel],
	  buttons: [{
	   text: 'Cerca',
	   handler:function(){
		  this.fireEvent("searchPubblicazione");
	   },
	   scope:this
	  },{
	   text: 'Reset',
	   handler:function(){
		  this.resetForm();
		  this.fireEvent("searchPubblicazione");
	   },
	   scope:this
	  },{
	   text: 'Chiudi',
	   handler:function(){
		  this.hide();
	   },
	   scope:this
	  }]
	});
  },

  show: function(){
    this.win.show();
  },

  hide: function(){
    if(this.formPanel.getForm().isDirty()){
	  Ext.Msg.show({
        title:'Resettare il filtro di ricerca ?',
        msg:"Resettare il filtro di ricerca?",
        buttons: Ext.Msg.YESNO,
        fn:function(btn){
          if (btn=='yes'){
            this.resetForm();
            this.fireEvent("searchPubblicazione");
            Ext.QuickTips.init();
            this.win.close();
          }else{
            this.win.hide();
          }
        },
        icon: Ext.MessageBox.QUESTION,
        scope:this
      });
    }
  },
  
  resetForm:function(){
	this.formPanel.getForm().reset();
  },

  buildForm:function(){
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
	this.dataInserimentoField=new Ext.form.DateField({
	  name:'data_inserimento',
	  fieldLabel:'Data'
	});
	this.oggettoField=new Ext.form.TextField({
	  name:'oggetto',
	  fieldLabel:'Oggetto'
	});
	this.autoreField=new Ext.form.TextField({
	  name:'autore',
	  fieldLabel:'Autore'
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
	  fieldLabel:'Numero protocollo'
	});
	this.annoProtocolloField=new Ext.form.NumberField({
	  name:'anno_protocollo',
	  fieldLabel:'Anno protocollo'
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
	this.nomeFile=new Ext.form.TextField({
	  fieldLabel:'Nome file',
	  name:'file_name'
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
	    this.nomeFile
	  ]
	});
  }
});