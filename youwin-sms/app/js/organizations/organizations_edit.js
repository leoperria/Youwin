Application.organizations.WinEdit = function(){
  this.init({
    winConfig:{
      title:'Modifica/aggiungi ente',
      height:500
    },
    loadUrl:'organizations/get',
    saveUrl:'organizations/save'
  });
  
};

Ext.extend(Application.organizations.WinEdit , Application.api.GenericForm, {
  
  getFormItems: function(){
	var store=new Ext.data.JsonStore({
        url: 'organizations/loadtype',
        root: 'results',
        fields: [{name:'ID_tipologia_ente',type:'int'},{name:'denominazione'}]
    });
    store.load();
      
    this.comboTipoEnte=new Ext.form.ComboBox({
      name:'ID_tipologia_ente',
      editable:false,
      fieldLabel:'Tipo ente',
      store:store,
      triggerAction:'all',
      forceSelection:true,
      displayField:'denominazione',
      valueField:'ID_tipologia_ente',
      hiddenName:'ID_tipologia_ente'
    });
    this.dataIscrizioneField=new Ext.form.DateField({
        fieldLabel: 'Data iscrizione',
        name:'data_iscrizione',
        allowBlank:false
    });
    
    this.denominazioneField=new Ext.form.TextField({
        fieldLabel: 'Denominazione',
        name:'denominazione',
        allowBlank:false
    });
    
    this.indirizzoField=new Ext.form.TextField({
        fieldLabel: 'Indirizzo',
        name:'indirizzo'
    });
    
    this.capField=new Ext.form.TextField({
      fieldLabel:'C.A.P.',
      name:'cap'
    });
    
    this.localitaField=new Ext.form.TextField({
        fieldLabel:'Località',
        name:'localita'
      });
    
    this.provinciaField=new Ext.form.ComboBox({
      fieldLabel:'Provincia',
      store:new Ext.data.Store({
        proxy: new Ext.data.HttpProxy({url: 'organizations/listprovince'}),
        reader:new Ext.data.JsonReader({
        root:'results',
        id:'ID_provincia'
        },[
          {name:'ID_provincia',type:'string'},
          {name:'provincia',type:'string'}
        ])
      }),
      triggerAction:'all',
      forceSelection:true,
      displayField:'provincia',
      valueField:'ID_provincia',
      hiddenName:'ID_provincia'
    });
    
    this.codFisField=new Ext.form.TextField({
      fieldLabel:'Codice fiscale',
      name:'codfis'
    });
    
    this.pIvaField=new Ext.form.TextField({
      fieldLabel:'P. IVA',
      name:'piva'
    });

    this.telField=new Ext.form.TextField({
      fieldLabel:'Telefono',
      name:'tel'
    });
    
    this.faxField=new Ext.form.TextField({
      fieldLabel:'Fax',
      name:'fax'
    });
    
    /*this.fileSimbolo=new Ext.ux.form.FileUploadField({
      emptyText: 'Seleziona il file',
      fieldLabel: 'File simbolo',
      name: 'file',
      buttonText: '',
      buttonCfg: {
        iconCls: 'upload-icon'
      }
    });*/
    
    return [
		  this.dataIscrizioneField,
		  this.comboTipoEnte,
		  this.denominazioneField,
		  this.indirizzoField,
		  this.capField,
		  this.localitaField,
		  this.provinciaField,
		  this.codFisField,
		  this.pIvaField,
		  this.telField,
		  this.faxField
    ];
    
  },
  
  newRecordInit:function(){
    var d=new Date();
    this.dataIscrizioneField.setValue(d.format('d/m/Y'));    
  }
  
});