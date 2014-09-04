Ext.namespace("Application.mittenti");
Application.mittenti.WinMittenti = function(){
  
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
          console.log(record.id);
          this.edit(record.id);
        break;

		case 'icon-delete':
		  this.deleteRecord(record.id);
		break;
      }
    },this);
    
  this.init({
    winConfig:{title:'Gestione mittenti',width:500},
    deleteUrl:'mittenti/delete',
    gridConfig:{plugins:[this.rowAction]}
  });
};

Ext.extend(Application.mittenti.WinMittenti , Application.apiGrid.WinList, {

  getStoreConfig:function(){

    return new Ext.data.JsonStore({
      url: 'mittenti/list',
      root:'results',
      totalProperty:'totalCount',
      id:'ID_mittente',
      fields:[
        {name:'ID_mittente',type:'int'},
        {name:'orgid',type:'int'},
        {name:'denominazione'}
      ]
    });
  },
  
  getColumnConfig:function(){
    
    return [
        { header:'ID', width:60, dataIndex:'ID_mittente'},
        { header:'denominazione', width:200, dataIndex:'denominazione'},
        this.rowAction
      ]
  },
  
  edit:function(id){
    this.editWin=new Application.mittenti.WinEdit();
    this.editWin.show(id);
    this.editWin.on('updated',function(){this.refreshPanel();},this);
  }
});