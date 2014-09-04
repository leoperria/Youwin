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
import java.util.HashMap;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.swing.JOptionPane;
import javax.swing.SwingWorker;
import com.omicronmedia.Fonts;
import com.omicronmedia.TaskOnWin;
import com.omicronmedia.YouWinTotem;

public class TaskOnWinPortaNuova implements TaskOnWin {

  private static final Logger logger = YouWinTotem.getLogger();
  public int TOL_SUB_UNIT_CODE; //  Task on Loose: codice SUB-UNIT di questo totem
  YouWinTotem totem;

  public void setParentObject(YouWinTotem totem) {
    this.totem=totem;
    this.TOL_SUB_UNIT_CODE = new Integer(totem.getConfig().getProperty("TOL_SUB_UNIT_CODE"));
  }

  public void exec(HashMap info, HashMap winInfo) {
    logger.log(Level.INFO, "TASK ON WIN **********************************************************************************************");
    (new WinnerPrintJob(info, winInfo)).execute();
  }

  
  /**
   * SwingWorker per stampare il biglietto in caso di vincita
   */
  public class WinnerPrintJob extends SwingWorker<String, Object> implements Printable {

    public static final double MM = 1 / 0.35277777 / 0.9682;
    private double current_y;
    private HashMap info;
    private HashMap winInfo;

    public WinnerPrintJob(HashMap info, HashMap winInfo) {
      this.info = info;
      this.winInfo = winInfo;
    }

    @Override
    public String doInBackground() {
      try {
        PrinterJob pPrinterJob = PrinterJob.getPrinterJob();
        pPrinterJob.setPrintService(totem.getPrintService());
        PageFormat pPageFormat = pPrinterJob.defaultPage();
        Paper pPaper = pPageFormat.getPaper();
        if (!totem.PRINT_IN_A4) {
          double W;
          double H;
          W = 60 * MM;
          H = 100 * MM;
          //System.out.println("W=" + W + " (" + (W / MM) + "mm)");
          //System.out.println("H=" + H + " (" + (H / MM) + "mm)");
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
      current_y = 3 * MM;
    }

    public int print(Graphics g, PageFormat pageFormat, int page) {
      if (page > 0) {
        return Printable.NO_SUCH_PAGE;
      }
      try {
        BufferedReader reader;
        String str;
        initPage();
        Graphics2D g2d = (Graphics2D) g;
        g2d.translate(pageFormat.getImageableX(), pageFormat.getImageableY());
        g2d.setPaint(Color.black);
        if (totem.PRINT_BORDERS) {
          Rectangle2D.Double border = new Rectangle2D.Double(0, 0, pageFormat.getImageableWidth(), pageFormat.getImageableHeight());
          g2d.draw(border);
        } else {
          Rectangle2D.Double border = new Rectangle2D.Double(0, pageFormat.getImageableHeight() - 1.0E-4 * MM, 1.0E-4 * MM, pageFormat.getImageableHeight());
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
        space(12.0);

        /*g2d.setFont(Fonts.calibri_9);
        drawCenteredString((String) info.get("nome_line_0"), g2d, pageFormat);
        space(8.41);*/
        
        g2d.setFont(Fonts.calibri_11_B);
        reader = new BufferedReader(new StringReader((String) info.get("nome_stampa")));
        while ((str = reader.readLine()) != null) {
          if (str.length() > 0) {
            drawCenteredString(str, g2d, pageFormat);
            space(6);
          }
        }
        space(13.53);
        /*g2d.setFont(Fonts.calibri_9);
        drawCenteredString((String) winInfo.get("win_timestamp"), g2d, pageFormat);
        space(13.53);*/
        g2d.setFont(Fonts.calibri_11_B);
        HashMap premio = (HashMap) winInfo.get("premio");
        drawCenteredString("Hai vinto", g2d, pageFormat);
        space(6);
        drawCenteredString(((String) premio.get("articolo")) + " " + ((String) premio.get("denominazione")), g2d, pageFormat);
        space(8.41);
        // Stampa section_bottom
        g2d.setFont(Fonts.calibri_9);
        reader = new BufferedReader(new StringReader((String) info.get("print_section_bottom")));
        while ((str = reader.readLine()) != null) {
          drawCenteredString(str, g2d, pageFormat);
          space(8);
        }
        space(11);
        // Stampa la privacy policy
        g2d.setFont(Fonts.comic_sans_ms_6);
        reader = new BufferedReader(new StringReader((String) info.get("print_privacy")));
        while ((str = reader.readLine()) != null) {
          if (str.length() > 0) {
            drawCenteredString(str, g2d, pageFormat);
            space(3);
          }
        }
        space(4);
        g2d.setFont(Fonts.arial_7);
        String bottomLine = "SU" + TOL_SUB_UNIT_CODE + " " + winInfo.get("win_timestamp");
        drawCenteredString(bottomLine, g2d, pageFormat);

      } catch (Exception x) {
        logger.log(Level.SEVERE, x.getMessage(), x);
      }
      return Printable.PAGE_EXISTS;
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
