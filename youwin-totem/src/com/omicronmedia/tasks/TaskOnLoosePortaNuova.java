package com.omicronmedia.tasks;

import java.awt.Color;
import java.awt.FontMetrics;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.geom.Rectangle2D;
import java.awt.print.PageFormat;
import java.awt.print.Paper;
import java.awt.print.Printable;
import java.awt.print.PrinterException;
import java.awt.print.PrinterJob;
import java.io.BufferedReader;
import java.io.StringReader;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Properties;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.swing.JOptionPane;
import javax.swing.SwingWorker;
import com.omicronmedia.Fonts;
import com.omicronmedia.Task;
import com.omicronmedia.YouWinTotem;
import org.apache.xmlrpc.XmlRpcException;
import org.apache.xmlrpc.client.XmlRpcClient;

/**
 *
 * @author utente
 */
public class TaskOnLoosePortaNuova implements Task {

  public int TOL_ONLINE_ID_Concorso; // Task on Loose: ID del concorso Online al quale si fa riferimento
  public int TOL_SUB_UNIT_CODE; //  Task on Loose: codice SUB-UNIT di questo totem
  private static final Logger logger = YouWinTotem.getLogger();
  YouWinTotem totem;
  XmlRpcClient rpcClient;
  Properties config;

  /**
   *
   */
  public void exec() {
    logger.log(Level.INFO, "AUX TASK ON LOOSE **********************************************************************************************");
    try {
      Object[] result = (Object[]) rpcClient.execute("youwin.getSMSIntegrationCodes", new Object[]{TOL_ONLINE_ID_Concorso, TOL_SUB_UNIT_CODE, new Integer(1)});
      ArrayList<HashMap> codes = new ArrayList<HashMap>();
      for (int i = 0; i < result.length; i++) {
        codes.add((HashMap) result[i]);
      }
      HashMap infoConcorso = (HashMap) rpcClient.execute("youwin.infoConcorso", new Object[]{totem.ID_concorso});
      (new CodiciPrintJob(infoConcorso, codes)).execute();
    } catch (XmlRpcException ex) {
      logger.log(Level.SEVERE, ex.getMessage(), ex);
    }
  }

  public void setParentObject(YouWinTotem totem) {
    this.totem = totem;
    this.config = totem.getConfig();
    this.TOL_ONLINE_ID_Concorso = new Integer(config.getProperty("TOL_ONLINE_ID_Concorso"));
    this.TOL_SUB_UNIT_CODE = new Integer(config.getProperty("TOL_SUB_UNIT_CODE"));
    this.rpcClient = totem.getRpcClient();
  }

  public class CodiciPrintJob extends SwingWorker<String, Object> implements Printable {

    private final static double CODE_INTERLINE = 8.97;
    public final static double MM = 1 / 0.35277777 / 0.9682;
    private double current_y;
    private ArrayList<HashMap> codes;
    private HashMap info;

    public CodiciPrintJob(HashMap info, ArrayList<HashMap> codes) {
      this.codes = codes;
      this.info = info;
    }

    @Override
    public String doInBackground() {
      try {
        PrinterJob pPrinterJob = PrinterJob.getPrinterJob();
        pPrinterJob.setPrintService(totem.getPrintService());
        PageFormat pPageFormat = pPrinterJob.defaultPage();
        Paper pPaper = pPageFormat.getPaper();

        if (!config.getProperty("PRINT_IN_A4").trim().equals("1")) {
          double W;
          double H;
          W = 60 * MM;
          if (codes.size() == 1) {
            H = 43 * MM;
          } else {
            H = 115 * MM + (codes.size() * 6.37 * MM);
          }
          // System.out.println("W=" + W + " (" + (W / MM) + "mm)");
          // System.out.println("H=" + H + " (" + (H / MM) + "mm)");
          pPaper.setSize(W, H);
          pPaper.setImageableArea(0, 0, W, H);
          pPageFormat.setPaper(pPaper);
          pPageFormat.setOrientation(PageFormat.PORTRAIT);
        }

        /*System.out.println("validated W=" + pPageFormat.getWidth());
        System.out.println("validated H=" + pPageFormat.getHeight());
        System.out.println("validated iX=" + pPageFormat.getImageableX());
        System.out.println("validated iY=" + pPageFormat.getImageableY());
        System.out.println("validated iW=" + pPageFormat.getImageableWidth());
        System.out.println("validated iH=" + pPageFormat.getImageableHeight());*/
        pPrinterJob.setPrintable(this, pPageFormat);
        pPrinterJob.print();
      } catch (PrinterException pex) {
        logger.log(Level.SEVERE, pex.getMessage(), pex);
        JOptionPane.showMessageDialog(null, "ERRORE: " + pex.getMessage(), "Errore Stampante", JOptionPane.ERROR_MESSAGE);
      }
      return "DONE";
    }

    private void initPage() {
      current_y = 0 * MM;
    }

    public int print(Graphics g, PageFormat pageFormat, int page) {
      if (page > 0) {
        return Printable.NO_SUCH_PAGE;
      }

      try {

        BufferedReader reader;
        String str;
        initPage();

       /* System.out.println("------- print(page=" + page + ")");
        System.out.println("real W=" + pageFormat.getWidth());
        System.out.println("real H=" + pageFormat.getHeight());
        System.out.println("real iX=" + pageFormat.getImageableX());
        System.out.println("real iY=" + pageFormat.getImageableY());
        System.out.println("real iW=" + pageFormat.getImageableWidth());
        System.out.println("real iH=" + pageFormat.getImageableHeight());*/

        Graphics2D g2d = (Graphics2D) g;
        g2d.translate(pageFormat.getImageableX(), pageFormat.getImageableY());
        g2d.setPaint(Color.black);


        if (totem.PRINT_BORDERS) {
          Rectangle2D.Double border = new Rectangle2D.Double(0, 0, pageFormat.getImageableWidth(), pageFormat.getImageableHeight());
          g2d.draw(border);
        } else {
          Rectangle2D.Double border = new Rectangle2D.Double(0, pageFormat.getImageableHeight() - 0.0001 * MM, 0.0001 * MM, pageFormat.getImageableHeight());
          g2d.draw(border);
        }

        g2d.setFont(Fonts.calibri_9_B);
        reader = new BufferedReader(new StringReader((String) info.get("societa_promotrice_stampa")));
        while ((str = reader.readLine()) != null) {
          if (str.length() > 0) {
            drawCenteredString(str, g2d, pageFormat);
            space(5.41);
          }
        }

        space(5);

      /*  g2d.setFont(Fonts.calibri_9);
        drawCenteredString((String) info.get("nome_line_0"), g2d, pageFormat);
        space(5);*/

        g2d.setFont(Fonts.calibri_11_B);
        reader = new BufferedReader(new StringReader((String) info.get("nome_stampa")));
        while ((str = reader.readLine()) != null) {
          if (str.length() > 0) {
            drawCenteredString(str, g2d, pageFormat);
            space(2);
          }
        }

        g2d.setFont(Fonts.calibri_9_B);
        space(7);
        drawCenteredString("Mi dispiace, non hai vinto !", g2d, pageFormat);
        g2d.setFont(Fonts.calibri_9);
        space(7);
        drawCenteredString("Puoi ancora vincere", g2d, pageFormat);
        space(3);
        if (codes.size() > 1) {
          drawCenteredString("con i CODICI GIOCO:", g2d, pageFormat);
        } else {
          drawCenteredString("con il CODICE GIOCO:", g2d, pageFormat);
        }

        space(7);
        g2d.setFont(Fonts.courier_14_B);
        for (HashMap c : codes) {
          drawCenteredString((String) c.get("code"), g2d, pageFormat);
          space(CODE_INTERLINE);
        }
        space(1);

        g2d.setFont(Fonts.calibri_9);
        drawCenteredString("Collegandoti al sito:", g2d, pageFormat);
        space(2);
        g2d.setFont(Fonts.calibri_12_I);
        drawCenteredString("www.portanuovaoristano.com", g2d, pageFormat);

       /* space(14);
        g2d.setFont(Fonts.calibri_9);
        drawCenteredString("Oppure inviando il codice", g2d, pageFormat);
        space(5);
        drawCenteredString("con un SMS al:", g2d, pageFormat);

        space(11.5);
        g2d.setFont(Fonts.calibri_12_I);
        drawCenteredString((String) info.get("SMS_phone_number"), g2d, pageFormat);
        space(14);*/


        /*    if (codes.size() > 1) {
        g2d.setFont(Fonts.calibri_9);
        drawCenteredString("per ciascun \"CODICE GIOCO\" e scopri", g2d, pageFormat);
        space(3.84);
        drawCenteredString("subito se hai vinto", g2d, pageFormat);
        } else {
        g2d.setFont(Fonts.calibri_9);
        drawCenteredString("con il \"CODICE GIOCO\" e scopri", g2d, pageFormat);
        space(3.84);
        drawCenteredString("subito se hai vinto", g2d, pageFormat);
        }

        if (codes.size() > 1) {
        space(17.3);
        g2d.setFont(Fonts.calibri_14_B);
        drawCenteredString("ATTENZIONE !!", g2d, pageFormat);
        space(5);
        g2d.setFont(Fonts.calibri_9);
        drawCenteredString("Manda tanti SMS quanti sono i codici e", g2d, pageFormat);
        space(5);
        drawCenteredString("metti un solo codice in ogni SMS.", g2d, pageFormat);
        }
         */

        // Stampa la privacy policy
     /*   g2d.setFont(Fonts.comic_sans_ms_6);
        reader = new BufferedReader(new StringReader((String) info.get("SMS_print_privacy")));
        while ((str = reader.readLine()) != null) {
          if (str.length() > 0) {
            drawCenteredString(str, g2d, pageFormat);
            space(3);
          }
        }
        */
        space(8);

        g2d.setFont(Fonts.arial_7);
        String bottomLine = "SU" + TOL_SUB_UNIT_CODE + " " + info.get("server_timestamp");
        drawCenteredString(bottomLine, g2d, pageFormat);
      } catch (Exception x) {
        logger.log(Level.SEVERE, x.getMessage(), x);
      }

      return (Printable.PAGE_EXISTS);
    }

    private void drawCenteredString(String str, Graphics2D g2d, PageFormat pf) {
      FontMetrics fontMetrics = g2d.getFontMetrics();
      double x = (double) (pf.getImageableWidth() / 2) - (fontMetrics.stringWidth(str) / 2);
      current_y += fontMetrics.getAscent() * 0.8;
      g2d.drawString(str, (float) x, (float) current_y);
    }

    private void space(double amount) {
      current_y += amount;
    }
  }
}
