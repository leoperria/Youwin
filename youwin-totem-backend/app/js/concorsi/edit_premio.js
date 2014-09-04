Concorsi.concorsi.WinEditPremio = function(id){
  this.init({
    winConfig:{
      title:'Modifica/aggiungi premio',
      height:300
    },
    loadUrl:'concorsi/get-premio',
    loadParams:{id:id},
    saveUrl:'concorsi/save-premio',
    saveParams:{id:id}
  });

};

Ext.extend(Concorsi.concorsi.WinEditPremio, Application.api.GenericForm, {

  getFormItems: function(){
	return [
      new Ext.form.TextField({
        fieldLabel: 'Codice',
        name:'codice'
      }),
      new Ext.form.TextField({
        fieldLabel: 'Articolo',
        name:'articolo'
      }),
      new Ext.form.TextField({
        fieldLabel: 'Denominazione',
        name:'denominazione'
      }),
      new Ext.form.TextField({
        fieldLabel: 'Qnt. totale',
        name:'qnt_totale'
      }),
      new Ext.form.TextField({
        fieldLabel: 'Valore',
        name:'valore'
      }),
      new Ext.form.TextField({
        fieldLabel: 'Importo',
        name:'importo'
      }),
      new Ext.form.TextField({
        fieldLabel: 'Test',
        name:'test'
      }),
      new Ext.form.TextField({
        fieldLabel: 'Screen name',
        name:'screen_name'
      })
    ];
  },

  newRecordInit:function(){}

});