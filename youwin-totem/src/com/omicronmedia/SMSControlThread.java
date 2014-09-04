package com.omicronmedia;


import java.io.IOException;
import java.util.ArrayList;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.apache.xmlrpc.XmlRpcException;
import org.apache.xmlrpc.client.XmlRpcClient;
import org.smslib.AGateway.GatewayStatuses;
import org.smslib.AGateway.Protocols;
import org.smslib.GatewayException;
import org.smslib.ICallNotification;
import org.smslib.IGatewayStatusNotification;
import org.smslib.IInboundMessageNotification;
import org.smslib.InboundMessage;
import org.smslib.InboundMessage.MessageClasses;
import org.smslib.Message.MessageTypes;
import org.smslib.OutboundMessage;
import org.smslib.Service;
import org.smslib.TimeoutException;
import org.smslib.modem.SerialModemGateway;

/**
 * Thread di controllo remoto tramite SMS
 */
class SMSControlThread extends Thread {

  private static final Logger logger = YouWinTotem.getLogger();
  Service srv;
  InboundNotification inboundNotification = new InboundNotification();
  CallNotification callNotification = new CallNotification();
  GatewayStatusNotification statusNotification = new GatewayStatusNotification();
  YouWinTotem totem;
  XmlRpcClient rpcClient;
  public String MODEM_PORT;
  public int MODEM_BAUD;
  public String MODEM_MANUFACTURER;
  public String MODEM_MODEL;
  public String MODEM_VALID_NUMBER_1;
  public String MODEM_VALID_NUMBER_2;

  SMSControlThread(YouWinTotem totem) {
    this.totem = totem;
    this.rpcClient = totem.getRpcClient();
    MODEM_PORT = totem.getConfig().getProperty("MODEM_PORT");
    MODEM_BAUD = new Integer(totem.getConfig().getProperty("MODEM_BAUD"));
    MODEM_MODEL = totem.getConfig().getProperty("MODEM_MODEL");
    MODEM_VALID_NUMBER_1 = totem.getConfig().getProperty("MODEM_VALID_NUMBER_1");
    MODEM_VALID_NUMBER_2 = totem.getConfig().getProperty("MODEM_VALID_NUMBER_2");
    logger.log(Level.INFO, "REMOTE MODEM MANAGEMENT = ON:\n");
  }

  @Override
  public void run() {
    try {
      logger.log(Level.INFO, "SMSCotrolThread INIT");
      srv = new Service();
      SerialModemGateway gateway = new SerialModemGateway("modem.com", MODEM_PORT, MODEM_BAUD, MODEM_MANUFACTURER, MODEM_MODEL);
      gateway.setProtocol(Protocols.PDU);
      gateway.setInbound(true);
      gateway.setOutbound(true);
      //gateway.setSimPin("0000");
      srv.setInboundNotification(inboundNotification);
      srv.setCallNotification(callNotification);
      srv.setGatewayStatusNotification(statusNotification);
      srv.addGateway(gateway);
      srv.startService();
      logger.log(Level.INFO, "Modem Information: \n" + "  Manufacturer: " + "{0}" + "\n" + "  Model: " + "{1}" + "\n" + "  Serial No: " + "{2}" + "\n" + "  SIM IMSI: " + "{3}" + "\n" + "  Signal Level: " + "{4}" + "%" + "\n" + "  Battery Level: " + "{5}%", new Object[]{gateway.getManufacturer(), gateway.getModel(), gateway.getSerialNo(), gateway.getImsi(), gateway.getSignalLevel(), gateway.getBatteryLevel()});
      while (true) {
        Thread.sleep(60 * 1000);
      }
    } catch (InterruptedException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    } catch (Exception ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    } finally {
      try {
        srv.stopService();
      } catch (TimeoutException ex) {
        logger.log(Level.SEVERE, ex.getMessage(), ex);
      } catch (GatewayException ex) {
        logger.log(Level.SEVERE, ex.getMessage(), ex);
      } catch (IOException ex) {
        logger.log(Level.SEVERE, ex.getMessage(), ex);
      } catch (InterruptedException ex) {
        logger.log(Level.SEVERE, ex.getMessage(), ex);
      }
    }
  }

  public class InboundNotification implements IInboundMessageNotification {

    public void process(String gatewayId, MessageTypes msgType, InboundMessage msg) {
      if (msgType == MessageTypes.INBOUND) {
        logger.log(Level.INFO, ">>> New Inbound message detected from Gateway: {0}", gatewayId);
      } else if (msgType == MessageTypes.STATUSREPORT) {
        logger.log(Level.INFO, ">>> New Inbound Status Report message detected from Gateway: {0}", gatewayId);
      }
      logger.log(Level.INFO, "NEW CONTROL SMS from {0}   msg={1}", new Object[]{msg.getOriginator(), msg.toString()});
      // Cancella TUTTI i messaggi in memoria nel modem!
      try {
        ArrayList<InboundMessage> msgList = new ArrayList<InboundMessage>();
        srv.readMessages(msgList, MessageClasses.ALL);
        for (InboundMessage msg2 : msgList) {
          srv.deleteMessage(msg2);
        }
      } catch (Exception e) {
        logger.log(Level.SEVERE, e.getMessage(), e);
      }
      String caller = "+" + msg.getOriginator();
      if (!caller.equals(MODEM_VALID_NUMBER_1) && !caller.equals(MODEM_VALID_NUMBER_2)) {
        logger.log(Level.WARNING, "Unauthorized SMS number {0}", caller);
      } else {
        try {
          String cmd = msg.getText().toLowerCase().trim();
          if (cmd.startsWith("prob ")) {
            double value = Double.parseDouble(cmd.substring(5));
            logger.log(Level.INFO, "Setting probability: p={0}", value);
            double valueCheck = Double.parseDouble((String) rpcClient.execute("youwin.setProb", new Object[]{totem.ID_concorso, new Double(value)}));
            if (value != valueCheck) {
              logger.log(Level.SEVERE, "setProb: failed probability value checking! {0} != {1}", new Object[]{value, valueCheck});
            } else {
              sendMessage(msg.getOriginator(), "OK Prob=" + valueCheck);
            }
          } else if (cmd.startsWith("info")) {
            String res = (String) rpcClient.execute("youwin.concorsoStats", new Object[]{totem.ID_concorso});
            sendMessage(msg.getOriginator(), res);
          } else {
            logger.log(Level.WARNING, "Unrecognized SMS command");
          }
        } catch (XmlRpcException ex) {
          Logger.getLogger(YouWinTotem.class.getName()).log(Level.SEVERE, null, ex);
        }
      }
    }
  }

  public class GatewayStatusNotification implements IGatewayStatusNotification {

    public void process(String gatewayId, GatewayStatuses oldStatus, GatewayStatuses newStatus) {
      logger.log(Level.INFO, ">>> Gateway Status change for {0}, OLD: {1} -> NEW: {2}", new Object[]{gatewayId, oldStatus, newStatus});
    }
  }

  public class CallNotification implements ICallNotification {

    public void process(String gatewayId, String callerId) {
      logger.log(Level.INFO, ">>> New call detected from Gateway: {0} : {1}", new Object[]{gatewayId, callerId});
      if (!callerId.equals(MODEM_VALID_NUMBER_1) && !callerId.equals(MODEM_VALID_NUMBER_2)) {
        logger.log(Level.WARNING, "Unauthorized Call number {0}", callerId);
        return;
      }
      String res;
      try {
        res = (String) rpcClient.execute("youwin.concorsoStats", new Object[]{totem.ID_concorso});
        sendMessage(callerId, res);
      } catch (XmlRpcException ex) {
        Logger.getLogger(YouWinTotem.class.getName()).log(Level.SEVERE, null, ex);
      }
    }
  }

  private void sendMessage(String phone, String msgText) {
    OutboundMessage msg2 = new OutboundMessage(phone, msgText);
    try {
      srv.sendMessage(msg2);
    } catch (TimeoutException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    } catch (GatewayException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    } catch (IOException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    } catch (InterruptedException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    }
  }
}
