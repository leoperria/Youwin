Ext.namespace("Concorsi.concorsi");
Concorsi.concorsi.WinGiorni = function(){

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
          console.log(record);
          this.edit(record.id);
        break;

		case 'icon-delete':
		  this.deleteRecord(record.id);
		break;
      }
    },this);

  this.init({
    winConfig:{title:'Giorni',width:800, iconCls: 'icon-date'},
    deleteUrl:'concorsi/delete-giorno',
    gridConfig:{plugins:[this.rowAction]},
    bbarConfig:{pageSize:60},
    gridButtons:['-',{
      text:'Svuota calendario',
      icon:'css/icons/cross.png',
      iconCls:'x-btn-text-icon',
      tooltip:{
        title:'Calendario',
        text:'Elimina tutte le date'
      },
      handler: function(){
        this.svuotaCalendario();
      },
      scope:this
    },'-',{
      text:'Crea intervallo',
      icon:'css/icons/add.png',
      iconCls:'x-btn-text-icon',
      tooltip:{
        title:'Calendario',
        text:'Crea un intervallo di date contiguo'
      },
      handler: function(){
        this.creaIntervallo();
      },
      scope:this
    }],
    stopRefreshOnLoad:false
  });
};

Ext.extend(Concorsi.concorsi.WinGiorni, Application.apiGrid.WinList, {

  getStoreConfig:function(){
    return new Ext.data.JsonStore({
      url: 'concorsi/list-giorni',
      root:'results',
      totalProperty:'totalCount',
      id:'ID',
      fields:[
        {name:'ID',type:'int'},
        {name:'data',type:'date',dateFormat:'Y-m-d'},
        {name:'ora_start'},
        {name:'ora_stop'},
        {name:'giorno_settimana',type:'int'}
      ]
    });
  },

  getColumnConfig:function(){
    return [
        {header:'Giorno',width:120,dataIndex:'giorno_settimana',renderer:function(value,metadata,record,rowIndex,colIndex,store){
          var g=Utils.utils.getDayOfWeek(value-1);
          var style="";
          if (value==6){
             style='style="color:blue"';
          }else if (value==7){
             style='style="color:red"';
          }
          return  '<span '+style+'>'+g+'</span>';
        }},
        {header:'Data', width:120, dataIndex:'data',renderer:Utils.utils.dateRenderer},
        {header:'Start', width:100, dataIndex:'ora_start'},
        {header:'Stop', width:100, dataIndex:'ora_stop'},
        this.rowAction
      ];
  },


  edit:function(id){
    var win=new Concorsi.concorsi.WinEditGiorno(id);
    win.on("updated",function(){
      this.refreshPanel();
    },this);
    win.show();
  },

  creaIntervallo:function(){
    var win=new Concorsi.concorsi.WinCreaIntervallo();
    win.on("updated",function(){
      this.refreshPanel();
    },this);
    win.show();
  },

  svuotaCalendario:function(){

    Ext.Msg.show({
      title:'Giornate',
      msg:"Cancellare intervallo di giornate ?",
      buttons: Ext.Msg.YESNO,
      fn:function(btn){
        if (btn=='yes'){
          Ext.Ajax.request({
            url:"concorsi/svuota-giornate",
            success:function(response,options){
              var result=Ext.decode(response.responseText);
              if (result.success==true){
                this.refreshPanel();
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

  }


});