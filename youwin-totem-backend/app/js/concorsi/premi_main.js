Concorsi.concorsi.WinPremi = function(){

  this.rowAction=new Ext.ux.grid.RowActions({
      actions:[{
        iconCls:'icon-edit',
        qtip:'Modifica'
      },{
        iconCls:'icon-delete',
        qtip:'Elimina'
      }],
        widthIntercept:Ext.isSafari ? 4 : 2,
        id:'actions'
    });

    this.rowAction.on('action',function(grid, record, action, row, col){
      switch(action) {
        case 'icon-edit':
          this.edit(record.id);
        break;

		case 'icon-delete':
		  this.deleteRecord(record.id);
		break;
      }
    },this);

  this.init({
    winConfig:{title:'Premi',width:800, iconCls: 'icon-date'},
    deleteUrl:'concorsi/delete-premio',
    gridConfig:{plugins:[this.rowAction]},
    bbarConfig:{pageSize:60},
    stopRefreshOnLoad:false
  });
};

Ext.extend(Concorsi.concorsi.WinPremi, Application.apiGrid.WinList, {

  getStoreConfig:function(){
    return new Ext.data.JsonStore({
      url: 'concorsi/list-premi',
      root:'results',
      totalProperty:'totalCount',
      id:'ID',
      fields:[
        {name:'ID',type:'int'},
        {name:'codice'},
        {name:'ID_concorso'},
        {name:'articolo'},
        {name:'denominazione'},
        {name:'qnt_totale',type:'int'},
        {name:'valore',type:'float'},
        {name:'importo',type:'float'}
      ]
    });
  },

  getColumnConfig:function(){
    return [
        {header:'ID', width:40, dataIndex:'ID'},
        {header:'Codice', width:50, dataIndex:'codice'},
        {header:'Articolo', width:60, dataIndex:'articolo'},
        {header:'Denominazione', width:250, dataIndex:'denominazione'},
        {header:'Qnt.totale', width:80, dataIndex:'qnt_totale'},
        {header:'Valore', width:80, dataIndex:'valore'},
        {header:'Importo', width:80, dataIndex:'importo'},
        this.rowAction
      ];
  },


  edit:function(id){
    var win=new Concorsi.concorsi.WinEditPremio(id);
    win.on("updated",function(){
      this.refreshPanel();
    },this);
    win.show();
  }

});