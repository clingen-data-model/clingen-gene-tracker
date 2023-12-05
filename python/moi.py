import xml.etree.ElementTree as ET

print('reading in file and getting root...')
root = ET.parse('/Users/yugen/Downloads/hp.owl').getroot()

def getSubclassesOf(owlClassUri):
    subClasses = root.findall('{http://www.w3.org/2002/07/owl#}Class/{http://www.w3.org/2000/01/rdf-schema#}subClassOf[@{http://www.w3.org/1999/02/22-rdf-syntax-ns#}resource="'+owlClassUri+'"]/..')
    if (len(subClasses) > 0):
        moi = []
        for subclass in subClasses:
            subclassUri = subclass.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}about')
            subclassDict = {
                'name': subclass.find('{http://www.w3.org/2000/01/rdf-schema#}label').text,
                'uri': subclassUri,
                'parentUri': owlClassUri 
            }
            moi.append(subclassDict)
            moi.append(getSubclassesOf(subclassUri))
    
    return moi

print("Building list")
moiList = getSubclassesOf('http://purl.obolibrary.org/obo/HP_0000005')

print(moiList)