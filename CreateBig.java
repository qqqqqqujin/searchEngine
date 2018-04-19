package csci572hw5;

import java.io.File;

import java.io.FileInputStream;

import java.io.PrintWriter;

import org.apache.tika.metadata.Metadata;



import org.apache.tika.parser.ParseContext;



import org.apache.tika.parser.html.HtmlParser;



import org.apache.tika.sax.BodyContentHandler;

public class CreateBig {


//	public static final String CSV = "/Users/jinqu/Desktop/572/HW4/USAToday/USATodayMap.csv";
//	public static final String INPUT_DIR = "/Users/jinqu/Desktop/572/HW4/USAToday/USAToday/";
	
	public static void main(String args[]) throws Exception {
		String op = "/Users/jinqu/Desktop/parse/big.txt";
		PrintWriter out = new PrintWriter(op);
		String dirPath = "/Users/jinqu/Desktop/572/HW4/USAToday/USAToday/";
		File dir = new File(dirPath);

		int count = 1;


		for (File file : dir.listFiles()) {

            //detecting the file type
            BodyContentHandler handler = new BodyContentHandler(-1);
            Metadata metadata = new Metadata();
            FileInputStream inputstream = new FileInputStream(file);
            ParseContext pcontext = new ParseContext();

            //Html parser
            HtmlParser htmlparser = new HtmlParser();
            htmlparser.parse(inputstream, handler, metadata, pcontext);


            out.println(handler.toString());
            String[] metadataNames = metadata.names();
            out.println(metadataNames);

        }
        out.close();
        out.flush();
				



		
		//writer.flush();
	}

}