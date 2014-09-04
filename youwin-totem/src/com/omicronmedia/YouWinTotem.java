/*
 * YOUWIN Totem 1.0
 *
 * By Leonardo Perria 2009
 * 
 */
package com.omicronmedia;

import com.omicronmedia.tools.CoolFormatter;
import com.jpackages.jflashplayer.FlashPanel;
import com.jpackages.jflashplayer.JFlashInvalidFlashException;
import com.jpackages.jflashplayer.JFlashLibraryLoadFailedException;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.FlowLayout;
import java.awt.Image;
import java.awt.Point;
import java.awt.Toolkit;
import java.awt.event.KeyEvent;
import java.awt.event.KeyListener;
import java.awt.image.MemoryImageSource;
import java.awt.print.PrinterJob;
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.PrintStream;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.HashMap;
import java.util.Properties;
import java.util.concurrent.LinkedBlockingQueue;
import java.util.logging.FileHandler;
import java.util.logging.Handler;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.print.PrintService;
import javax.swing.ImageIcon;
import javax.swing.JFrame;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.SwingWorker;
import org.apache.xmlrpc.XmlRpcException;
import org.apache.xmlrpc.client.XmlRpcClient;
import org.apache.xmlrpc.client.XmlRpcClientConfigImpl;

/**
 *
 * @author Leonardo Perria '09
 *
 *
 *
 *
 */
public final class YouWinTotem extends JFrame {

  //
  //Parametri generali
  //
  private final static String APP_VERSION = "1.1";  // Versione dell'applicazione
  public String SERVER_XMLRPC_PATH;  // PATH del server XML-RPC da aggiungere a SERVER_BASE_URL
  public String SERVER_BASE_URL; // URL base del server
  public String PRINTER_NAME;  // Nome della stampante locale
  public boolean PRINT_IN_A4; // 1 = Stampare in A4
  public boolean PRINT_BORDERS; // 1 = Stampare i bordi
  public boolean FULLSCREEN; // 1 = L'applicazione viene lanciata in fullscreen
  public int ID_concorso;  // ID del concorso locale, tipicamente 1
  public boolean TASK_ON_LOOSE; // Lancia un comportamento aggiuntivo in caso di perdita
  public String TASK_ON_WIN_CLASS; // La classe del task da eseguire in caso di vincita
  public String TASK_ON_LOOSE_CLASS; // La classe del task da eseguire in caso di perdita
  //
  //Parametri interfaccia di controllo remoto via SMS
  //
  public boolean MODEM;
  //
  // Variabili
  //
  Properties config;
  private static final Logger logger = Logger.getLogger("YouWinTotem");
  public Dimension CONFIG_SCREEN_DIMENSION = new Dimension(1024, 768);
  private XmlRpcClient rpcClient;
  private PrintService printService = null;
  private FlashPanel flashPanel;
  private LinkedBlockingQueue<String> msgQueue;
  private String typingBuffer = "";
  private JPanel contentPanel;
  private TaskOnWin taskOnWin;
  private Task taskOnLoose;

  /**
   * Costruttore
   */
  public YouWinTotem() throws Exception {

    // Inizializzazioni
    super("YouWin-Totem " + YouWinTotem.APP_VERSION);

    //***** Configura il logger
    setupLogger();

    logger.log(Level.INFO, "YouWIN Totem  " + YouWinTotem.APP_VERSION + " - Started... **********************************************************************************************");

    //**** Carica la configurazione
    loadConfig();

    //**** Inizializza la connessione al server XMLRPC
    setupXmlRpc();

    //***** Inizializza la stampante
    setupPrinter();

    //***** Inizializza l'interfaccia grafica
    setupUI();

    //***** Inizializza l'interfaccia di controllo remoto tramite SMS
    if (MODEM) {
      (new SMSControlThread(this)).start();
    }

    taskOnWin = (TaskOnWin) createObjectByClassName(TASK_ON_WIN_CLASS);
    taskOnWin.setParentObject(com.omicronmedia.YouWinTotem.this);

    if (TASK_ON_LOOSE) {
      taskOnLoose = (Task) createObjectByClassName(TASK_ON_LOOSE_CLASS);
      taskOnLoose.setParentObject(com.omicronmedia.YouWinTotem.this);
    }

    /* TEST
    HashMap info = (HashMap) rpcClient.execute("youwin.infoConcorso", new Object[]{ID_concorso});
    HashMap winInfo = (HashMap) rpcClient.execute("youwin.gamble", new Object[]{ID_concorso, "AA"});
    (new WinnerPrintJob(info, winInfo)).execute();*/

    // Crea e fa partire il thread principale che gestisce la coda delle giocate
    msgQueue = new LinkedBlockingQueue<String>();
    (new GamblerThread(msgQueue)).start();
  }

  private Object createObjectByClassName(String className) {
    try {
      Class cls = Class.forName(className);
      logger.log(Level.INFO, "Instantiating new object of class {0}", className);
      return cls.newInstance();
    } catch (ClassNotFoundException ex) {
      logger.log(Level.SEVERE, null, ex);
    } catch (InstantiationException ex) {
      logger.log(Level.SEVERE, null, ex);
    } catch (IllegalAccessException ex) {
      logger.log(Level.SEVERE, null, ex);
    }
    return null;
  }

  /**
   * Carica la configurazione
   */
  private void loadConfig() {

    config = new Properties();
    try {
      // Carica la configurazione
      config.load(new FileInputStream("totem.properties"));
      ByteArrayOutputStream baos = new ByteArrayOutputStream();
      config.list(new PrintStream(baos));
      logger.log(Level.INFO, "Configurazione:\n{0}", baos.toString());
    } catch (Exception ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
      System.err.print("Errori! vedere il file di log...");
      System.exit(1);
    }

    SERVER_XMLRPC_PATH = config.getProperty("SERVER_XMLRPC_PATH");
    SERVER_BASE_URL = config.getProperty("SERVER_BASE_URL");
    PRINTER_NAME = config.getProperty("PRINTER_NAME");
    PRINT_IN_A4 = config.getProperty("PRINT_IN_A4").equals("1");
    PRINT_BORDERS = config.getProperty("PRINT_BORDERS").equals("1");
    FULLSCREEN = config.getProperty("FULLSCREEN").equals("1");
    ID_concorso = new Integer(config.getProperty("ID_concorso"));
    TASK_ON_LOOSE = config.getProperty("TASK_ON_LOOSE").equals("1");
    MODEM = config.getProperty("MODEM").equals("1");
    TASK_ON_WIN_CLASS = config.getProperty("TASK_ON_WIN_CLASS");
    TASK_ON_LOOSE_CLASS = config.getProperty("TASK_ON_LOOSE_CLASS");
  }

  /**
   * Thread di gioco principale
   *
   */
  class GamblerThread extends Thread {

    private final LinkedBlockingQueue<String> msgQueue;

    public GamblerThread(LinkedBlockingQueue<String> msgQueue) {
      this.msgQueue = msgQueue;
    }

    @Override
    @SuppressWarnings("SleepWhileHoldingLock")
    public void run() {

      HashMap winInfo;

      try {

        String pageBasePath = "../animazioni_concorso/";

        logger.log(Level.INFO, "Entering GamblerThread -------------------------------------------------:");

        Integer res = (Integer) rpcClient.execute("youwin.calibrate", new Object[]{});
        logger.log(Level.INFO, "TEST DI CALIBRAZIONE: N.vincite su 10000 lanci a p=0.5   ----> {0}", res);

        HashMap info = (HashMap) rpcClient.execute("youwin.infoConcorso", new Object[]{ID_concorso});
        logger.log(Level.INFO, "SERVER_TIMESTAMP={0}", info.get("server_timestamp"));

        String screen_idle = pageBasePath + (String) info.get("screen_idle");
        logger.log(Level.INFO, "Loading flash page: {0}", screen_idle);
        flashPanel.callFlashFunction("addPage", new Object[]{screen_idle});

        String screen_loose = pageBasePath + (String) info.get("screen_loose");
        logger.log(Level.INFO, "Loading flash page: {0}", screen_loose);
        flashPanel.callFlashFunction("addPage", new Object[]{screen_loose});

        String screen_wait = pageBasePath + (String) info.get("screen_wait");
        logger.log(Level.INFO, "Loading flash page: {0}", screen_wait);
        flashPanel.callFlashFunction("addPage", new Object[]{screen_wait});

        Object[] screen_names = (Object[]) info.get("screen_names");
        for (Object screen_name : screen_names) {
          HashMap sc = (HashMap) screen_name;
          logger.log(Level.INFO, "Loading flash page: {0}{1}", new Object[]{pageBasePath, (String) sc.get("screen_name")});
          flashPanel.callFlashFunction("addPage", new Object[]{pageBasePath + (String) sc.get("screen_name")});
        }
        flashPanel.callFlashFunction("loadPages", new Object[]{});
        int screen_win_time = Integer.parseInt((String) info.get("screen_win_time"));
        int screen_wait_time = Integer.parseInt((String) info.get("screen_wait_time"));
        int screen_loose_time = Integer.parseInt((String) info.get("screen_loose_time"));

        // TODO: Pausa per caricamento pagine... da sostituire con un sistema di sincronizzazione più sofisticato.
        Thread.sleep(1500);

        while (true) {

          if (msgQueue.size() == 0) {
            flashPanel.callFlashFunction("showPage", new Object[]{screen_idle});
          }
          Thread.sleep(100);
          String code = msgQueue.take(); //blocking...
          logger.log(Level.INFO, "New code entered: {0}", code);

          boolean winner = false;
          logger.log(Level.INFO, "Gamble: ID_concorso={0}  code={1}", new Object[]{ID_concorso, code});
          winInfo = (HashMap) rpcClient.execute("youwin.gamble", new Object[]{new Integer(ID_concorso), code});

          if (winInfo.containsKey("error") && (Boolean) winInfo.get("error")) {
            logger.log(Level.SEVERE, "Server error: {0}", ((String) winInfo.get("msg")));
          } else {
            flashPanel.callFlashFunction("showPage", new Object[]{screen_wait});
            Thread.sleep(screen_wait_time);
            winner = (Boolean) winInfo.get("winner");
            logger.log(Level.INFO, "Winner: {0}", (winner ? "YES" : "NO"));
            if (!winner) {
              flashPanel.callFlashFunction("showPage", new Object[]{screen_loose});
              if (TASK_ON_LOOSE) {
                taskOnLoose.exec();
              }
              Thread.sleep(screen_loose_time);
            } else {
              HashMap premio = (HashMap) winInfo.get("premio");
              logger.log(Level.INFO, "Prize: {0}", (String) premio.get("denominazione"));
              taskOnWin.exec(info, winInfo);
              flashPanel.callFlashFunction("showPage", new Object[]{pageBasePath + (String) premio.get("screen_name")});
              Thread.sleep(screen_win_time);
            }
          }
        }
      } catch (InterruptedException ex) {
        return;
      } catch (XmlRpcException ex) {
        logger.log(Level.SEVERE, ex.getMessage(), ex);
        JOptionPane.showMessageDialog(YouWinTotem.this, "ERRORE: " + ex.getMessage(), "Error XMLRPC", JOptionPane.ERROR_MESSAGE);
      }

    }
  }

  public void notifyPageLoad(String pageLoaded) {
    logger.log(Level.INFO, "LOADED:{0}", pageLoaded);
  }

  /**
   * SwingWorker per uscire dal programma
   */
  class ExitFromApp extends SwingWorker<String, Object> {

    @Override
    public String doInBackground() {
      int ret = JOptionPane.showConfirmDialog(null, "Si sta per terminare l'applicazione, si vuole procedere?", "YouWin - Totem", JOptionPane.YES_NO_OPTION);
      if (ret == JOptionPane.YES_OPTION) {
        logger.log(Level.INFO, "Programma terminato");
        System.exit(0);
      }
      return "";
    }
  }

  /**
   * Handler (virtualizzato) per la pressione di un tasto sulla tastiera.
   *
   * @param keyPressed
   */
  public void keyPressedProxy(int keyPressed) {
    System.out.println("KEY='" + keyPressed+ "   ["+(char)keyPressed+"]");

    if ((char) keyPressed == 65) {
      System.out.println("PRINT TEST");
      (new TestPrintJob("TEST STAMPA", this)).execute();
    }

    if (keyPressed == 10 || keyPressed == 13) {
      try {
        System.out.println("INSERISCO il buffer: '"+typingBuffer+"'");
        msgQueue.put(typingBuffer);
      } catch (InterruptedException ex) {
        logger.log(Level.SEVERE, ex.getMessage(), ex);
      }
      this.typingBuffer = "";
    } else {
      char c = (char) keyPressed;
      if (Character.isLetterOrDigit(c)) {
        this.typingBuffer += c;
      }
    }
    try {
      if (keyPressed == KeyEvent.VK_ESCAPE) {
        (new ExitFromApp()).execute();
      }
    } catch (RuntimeException e) {
      logger.log(Level.SEVERE, e.getMessage(), e);
    }
  }

  /**
   * Chiamato da actionscript
   *
   * @param event
   */
  public void notifyFlashMouseEvent(String event) {
  }

  /**
   * Chiamato da actionscript
   *
   * @param event
   */
  public void notifyFlashKeyEvent(String event, double keyCode) {
    keyPressedProxy((int) keyCode);
  }

  /**
   * Crea un pannello Flash
   *
   * @param path
   * @return FlashPanel
   */
  public FlashPanel createFlashPanel(String url) throws JFlashLibraryLoadFailedException, JFlashInvalidFlashException {
    try {
      FlashPanel fp = new YouWinFlashPanel(new URL(url));
      fp.setPreferredSize(CONFIG_SCREEN_DIMENSION);
      fp.setFlashCallObject(this);
      fp.setVisible(true);
      return fp;
    } catch (MalformedURLException e) {
      return null;
    }
  }

  /**
   * Crea un pannello Flash
   *
   * @param path
   * @return FlashPanel
   */
  public FlashPanel createFlashPanel(File file) {
    FlashPanel fp = null;
    try {
      fp = new YouWinFlashPanel(file);
      fp.setPreferredSize(CONFIG_SCREEN_DIMENSION);
      fp.setFlashCallObject(this);
      fp.setVisible(true);
    } catch (JFlashLibraryLoadFailedException ex) {
      Logger.getLogger(YouWinTotem.class.getName()).log(Level.SEVERE, null, ex);
    } catch (JFlashInvalidFlashException ex) {
      Logger.getLogger(YouWinTotem.class.getName()).log(Level.SEVERE, null, ex);
    } catch (FileNotFoundException ex) {
      Logger.getLogger(YouWinTotem.class.getName()).log(Level.SEVERE, null, ex);
    }
    return fp;
  }

  /**
   * Configura il logger
   */
  private void setupLogger() {

    logger.setLevel(Level.ALL);
    Handler[] handlers = logger.getParent().getHandlers();
    for (Handler h : handlers) {
      h.setFormatter(new CoolFormatter());
    }

    try {
      FileHandler fh = new FileHandler("YouWinTotem.log", true);
      fh.setFormatter(new CoolFormatter());
      logger.getParent().addHandler(fh);
    } catch (IOException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    } catch (SecurityException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    }


  }

  /**
   *  Configura la connessione al server XMLRPC
   */
  public void setupXmlRpc() {
    String xmlRpcUrl = SERVER_BASE_URL + "/" + SERVER_XMLRPC_PATH;
    logger.log(Level.INFO, "INIZIALIZZAZIONE XMLRPC CLIENT: ''{0}''", xmlRpcUrl);
    XmlRpcClientConfigImpl rpc_config = new XmlRpcClientConfigImpl();
    try {
      rpc_config.setServerURL(new URL(xmlRpcUrl));
    } catch (MalformedURLException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    }
    rpcClient = new XmlRpcClient();
    rpcClient.setConfig(rpc_config);
    try {
      String test = (String) rpcClient.execute("youwin.test", new Object[]{ID_concorso});
      logger.log(Level.INFO, "XMLRPC: test={0}", test);

    } catch (XmlRpcException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
      System.exit(1);
    }
  }

  /**
   * Configura la stampante
   *
   * @throws java.lang.Exception
   */
  public void setupPrinter() throws Exception {
    logger.log(Level.INFO, "INIZIALIZZAZIONE STAMPANTE ''{0}''", this.PRINTER_NAME);
    PrintService[] printers = PrinterJob.lookupPrintServices();
    boolean found = false;
    for (int i = 0; i < printers.length; i++) {
      PrintService ps = printers[i];
      if (ps.getName().equalsIgnoreCase(this.PRINTER_NAME)) {
        printService = ps;
        found = true;
        break;
      }
    }
    if (!found) {
      throw new Exception("ATTENZIONE: Stampante '" + this.PRINTER_NAME + "' non trovata nel sistema.");
    } else {
      logger.log(Level.INFO, "Found {0}", printService.getName());
    }
  }

  /**
   * Configura l'interfaccia grafica
   */
  public void setupUI() {
    logger.log(Level.INFO, "INIZIALIZZAZIONE INTERFACCIA GRAFICA {0}x{1}", new Object[]{this.CONFIG_SCREEN_DIMENSION.width, this.CONFIG_SCREEN_DIMENSION.height});
    this.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
    this.getGlassPane().setVisible(true);

    if (this.FULLSCREEN) {
      this.setFullScreen();
    } else {
      this.setSize(this.CONFIG_SCREEN_DIMENSION);
    }

    ImageIcon img = new ImageIcon("media/logo-kinesistemi-gigante.gif");
    this.setIconImage(img.getImage());
    contentPanel = (JPanel) this.getContentPane();
    contentPanel.setLayout(new FlowLayout(FlowLayout.CENTER, 0, 0));
    this.addKeyListener(new KeyListener() {

      public void keyPressed(KeyEvent event) {
        keyPressedProxy(event.getKeyCode());
      }

      public void keyReleased(KeyEvent event) {
      }

      public void keyTyped(KeyEvent event) {
      }
    });

    FlashPanel.installFlash();
    FlashPanel.setRequiredFlashVersion("9");
    /*try {
    // flashPanel = this.createFlashPanel(SERVER_BASE_URL + "/" + SERVER_MEDIA_PATH + "/concorso.swf");
    } catch (JFlashLibraryLoadFailedException ex) {
    Logger.getLogger(YouWinTotem.class.getName()).log(Level.SEVERE, null, ex);
    } catch (JFlashInvalidFlashException ex) {
    Logger.getLogger(YouWinTotem.class.getName()).log(Level.SEVERE, null, ex);
    }*/
    flashPanel = this.createFlashPanel(new File("media/flashpanel.swf"));
    contentPanel.add(flashPanel);
    this.setVisible(true);
  }

  /**
   * Imposta la modalità a tutto schermo
   */
  public void setFullScreen() {
    this.setCursor(YouWinTotem.getTransparentCursor());
    this.setUndecorated(true);
    this.setSize(this.getToolkit().getScreenSize());
    /*    GraphicsDevice dev1 = GraphicsEnvironment.getLocalGraphicsEnvironment().getDefaultScreenDevice();
    dev1.setFullScreenWindow(this);*/
  }

  /**
   * Crea un cursore trasparente
   * @return Cursor
   */
  public static Cursor getTransparentCursor() {
    int[] pixels = new int[16 * 16];
    Image image = Toolkit.getDefaultToolkit().createImage(new MemoryImageSource(16, 16, pixels, 0, 16));
    return Toolkit.getDefaultToolkit().createCustomCursor(image, new Point(0, 0), "invisibleCursor");
  }

  public static void main(String[] args) {
    /*try {
    UIManager.setLookAndFeel("com.sun.java.swing.plaf.motif.MotifLookAndFeel");
    } catch (Exception e) {
    System.err.println("Could not load LookAndFeel");
    }*/
    FlashPanel.setRegistrationKey("4JH3-8GP5-CNHJ-5CIP-4AHM-6AYM");

    try {
      YouWinTotem youWinTotem = new YouWinTotem();
    } catch (Exception ex) {
      System.err.println("ERRORE: " + ex.getMessage());
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    }
  }

  public Properties getConfig() {
    return config;
  }

  public static Logger getLogger() {
    return logger;
  }

  public PrintService getPrintService() {
    return printService;
  }

  public XmlRpcClient getRpcClient() {
    return rpcClient;
  }
}
