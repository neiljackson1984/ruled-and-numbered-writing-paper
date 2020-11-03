cd /D %~dp0

@rem 2020/11/02
php makeNumberedSheets.php --prefix="jenelle-" --initialNumber=2000 --numberOfSheets=200 --templateFile=ruled-writing-paper.tex  --outputDirectory=generated

@rem START HERE -->
@rem php makeNumberedSheets.php --prefix="jenelle-" --initialNumber=2200 --numberOfSheets=200 --templateFile=ruled-writing-paper.tex  --outputDirectory=generated
@rem php makeNumberedSheets.php --prefix="jenelle-" --initialNumber=2400 --numberOfSheets=400 --templateFile=ruled-writing-paper.tex  --outputDirectory=generated
@rem php makeNumberedSheets.php --prefix="jenelle-" --initialNumber=2800 --numberOfSheets=400 --templateFile=ruled-writing-paper.tex  --outputDirectory=generated



