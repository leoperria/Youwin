Ext.namespace("Application.categorie");
Application.categorie.WinCategorie = function(){
  
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
    winConfig:{title:'Gestione categorie',width:500},
    deleteUrl:'categorie/delete',
    gridConfig:{plugins:[this.rowAction]}
  });
};

Ext.extend(Application.categorie.WinCategorie , Application.apiGrid.WinList, {

  getStoreConfig:function(){

    return new Ext.data.JsonStore({
      url: 'categorie/list',
      root:'results',
      totalProperty:'totalCount',
      id:'ID_categoria',
      fields:[
        {name:'ID_categoria',type:'int'},
        {name:'orgid',type:'int'},
        {name:'order_id',type:'int'},
        {name:'denominazione'}
      ]
    });
  },
  
  getColumnConfig:function(){
    
    return [
        { header:'ID', width:60, dataIndex:'ID_categoria'},
        { header:'denominazione', width:200, dataIndex:'denominazione'},
        { header:'Ordine', width:60, dataIndex:'order_id'},
        this.rowAction
      ]
  },
  
  edit:function(id){
    this.editWin=new Application.categorie.WinEdit();
    this.editWin.show(id);
    this.editWin.on('updated',function(){this.refreshPanel();},this);
  }
});