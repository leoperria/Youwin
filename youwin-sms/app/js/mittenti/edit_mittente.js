Application.mittenti.WinEdit = function(){
  this.init({
    winConfig:{
      title:'Modifica/aggiungi mittente',
      height:150
    },
    loadUrl:'mittenti/get',
    saveUrl:'mittenti/save'
  });
};

Ext.extend(Application.mittenti.WinEdit , Application.api.GenericForm, {
  
  getFormItems: function(){
    return [new Ext.form.TextField({fieldLabel: 'Denominazione',name:'denominazione'})];
  },
  
  newRecordInit:function(){}

});