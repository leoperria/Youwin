Application.categorie.WinEdit = function(){
  this.init({
    winConfig:{
      title:'Modifica/aggiungi categoria',
      height:250
    },
    loadUrl:'categorie/get',
    saveUrl:'categorie/save'
  });
};

Ext.extend(Application.categorie.WinEdit , Application.api.GenericForm, {
  
  getFormItems: function(){
    return [new Ext.form.TextField({fieldLabel: 'Denominazione',name:'denominazione'}),new Ext.form.NumberField({fieldLabel: 'Ordinamento',name:'order_id'})];
  },
  
  newRecordInit:function(){}

});