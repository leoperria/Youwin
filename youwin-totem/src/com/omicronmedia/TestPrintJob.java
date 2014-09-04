package com.omicronmedia;

import java.awt.Color;
import java.awt.Font;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.print.PageFormat;
import java.awt.print.Paper;
import java.awt.print.Printable;
import java.awt.print.PrinterException;
import java.awt.print.PrinterJob;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.swing.JOptionPane;
import javax.swing.SwingWorker;

/**
 * PrintJob di test
 */
class TestPrintJob extends SwingWorker<String, Object> implements Printable {

  private static final Logger logger = YouWinTotem.getLogger();
  private String messaggio;
  YouWinTotem totem;

  public TestPrintJob(String messaggio, YouWinTotem totem) {
    this.totem = totem;
    this.messaggio = messaggio;
  }

  @Override
  public String doInBackground() {
    PrinterJob printJob = PrinterJob.getPrinterJob();
    try {
      printJob.setPrintService(totem.getPrintService());
      Paper paper = new Paper();
      double p_width = printJob.defaultPage().getPaper().getWidth();
      double p_height = 284;
      paper.setSize(p_width, p_height);
      paper.setImageableArea(0, 0, p_width, p_height);
      PageFormat pf = new PageFormat();
      pf.setOrientation(PageFormat.PORTRAIT);
      pf.setPaper(paper);
      printJob.setPrintable(this, pf);
      printJob.print();
    } catch (PrinterException pex) {
      logger.log(Level.SEVERE, pex.getMessage(), pex);
      JOptionPane.showMessageDialog(totem, "ERRORE: " + pex.getMessage(), "Errore Stampante", JOptionPane.ERROR_MESSAGE);
    }
    return "DONE";
  }

  public int print(Graphics g, PageFormat pageFormat, int page) {
    if (page > 0) {
      return Printable.NO_SUCH_PAGE;
    }
    Graphics2D g2d = (Graphics2D) g;
    g2d.translate(pageFormat.getImageableX(), pageFormat.getImageableY());
    g2d.setPaint(Color.black);
    //  Rectangle2D.Double border = new Rectangle2D.Double(0, 0, pageFormat.getImageableWidth(), pageFormat.getImageableHeight());
    // g2d.draw(border);
    String titleText = this.messaggio;
    Font titleFont = new Font("helvetica", Font.BOLD, 9);
    g2d.setFont(titleFont);
    g2d.drawString(titleText, 0, 7);
    return Printable.PAGE_EXISTS;
  }
}
