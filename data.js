// Data Pilahin: tambah kategori, kata kunci, atau soal kuis di sini
const DATA = {
  "categories": [
    {
      "slug": "organik",
      "name": "Organik",
      "color": "#3E9B4F",
      "bin": "hijau",
      "examples": "Sisa makanan, daun, kulit buah, ranting"
    },
    {
      "slug": "plastik",
      "name": "Plastik",
      "color": "#E8B923",
      "bin": "kuning",
      "examples": "Botol, gelas, sedotan, kantong, kemasan"
    },
    {
      "slug": "kertas",
      "name": "Kertas",
      "color": "#2F7DE1",
      "bin": "biru",
      "examples": "Kardus, kertas HVS, koran, buku bekas"
    },
    {
      "slug": "b3",
      "name": "B3",
      "color": "#D8453B",
      "bin": "merah",
      "examples": "Baterai, lampu bekas, obat kedaluwarsa, cat"
    },
    {
      "slug": "residu",
      "name": "Residu",
      "color": "#7C8A85",
      "bin": "abu-abu",
      "examples": "Tisu kotor, popok, puntung rokok, pecahan keramik"
    },
    {
      "slug": "elektronik",
      "name": "Elektronik",
      "color": "#8A56D6",
      "bin": "ungu (e-waste)",
      "examples": "Charger rusak, kabel, headset, mouse"
    }
  ],
  "items": [
    {
      "keyword": "kulit pisang",
      "category": "organik"
    },
    {
      "keyword": "kulit buah",
      "category": "organik"
    },
    {
      "keyword": "sisa nasi",
      "category": "organik"
    },
    {
      "keyword": "sisa makanan",
      "category": "organik"
    },
    {
      "keyword": "daun",
      "category": "organik"
    },
    {
      "keyword": "ranting",
      "category": "organik"
    },
    {
      "keyword": "tulang",
      "category": "organik"
    },
    {
      "keyword": "ampas kopi",
      "category": "organik"
    },
    {
      "keyword": "kulit telur",
      "category": "organik"
    },
    {
      "keyword": "sayur busuk",
      "category": "organik"
    },
    {
      "keyword": "rumput",
      "category": "organik"
    },
    {
      "keyword": "botol plastik",
      "category": "plastik"
    },
    {
      "keyword": "gelas plastik",
      "category": "plastik"
    },
    {
      "keyword": "sedotan",
      "category": "plastik"
    },
    {
      "keyword": "kantong plastik",
      "category": "plastik"
    },
    {
      "keyword": "kresek",
      "category": "plastik"
    },
    {
      "keyword": "kemasan",
      "category": "plastik"
    },
    {
      "keyword": "botol",
      "category": "plastik"
    },
    {
      "keyword": "tutup botol",
      "category": "plastik"
    },
    {
      "keyword": "bungkus snack",
      "category": "plastik"
    },
    {
      "keyword": "kardus",
      "category": "kertas"
    },
    {
      "keyword": "hvs",
      "category": "kertas"
    },
    {
      "keyword": "koran",
      "category": "kertas"
    },
    {
      "keyword": "buku",
      "category": "kertas"
    },
    {
      "keyword": "majalah",
      "category": "kertas"
    },
    {
      "keyword": "kertas",
      "category": "kertas"
    },
    {
      "keyword": "amplop",
      "category": "kertas"
    },
    {
      "keyword": "kotak susu",
      "category": "kertas"
    },
    {
      "keyword": "baterai",
      "category": "b3"
    },
    {
      "keyword": "lampu",
      "category": "b3"
    },
    {
      "keyword": "obat",
      "category": "b3"
    },
    {
      "keyword": "cat",
      "category": "b3"
    },
    {
      "keyword": "pestisida",
      "category": "b3"
    },
    {
      "keyword": "aki",
      "category": "b3"
    },
    {
      "keyword": "termometer",
      "category": "b3"
    },
    {
      "keyword": "sprai",
      "category": "b3"
    },
    {
      "keyword": "tinta printer",
      "category": "b3"
    },
    {
      "keyword": "tisu",
      "category": "residu"
    },
    {
      "keyword": "popok",
      "category": "residu"
    },
    {
      "keyword": "puntung",
      "category": "residu"
    },
    {
      "keyword": "rokok",
      "category": "residu"
    },
    {
      "keyword": "keramik",
      "category": "residu"
    },
    {
      "keyword": "pembalut",
      "category": "residu"
    },
    {
      "keyword": "pecahan",
      "category": "residu"
    },
    {
      "keyword": "masker bekas",
      "category": "residu"
    },
    {
      "keyword": "charger",
      "category": "elektronik"
    },
    {
      "keyword": "kabel",
      "category": "elektronik"
    },
    {
      "keyword": "headset",
      "category": "elektronik"
    },
    {
      "keyword": "earphone",
      "category": "elektronik"
    },
    {
      "keyword": "mouse",
      "category": "elektronik"
    },
    {
      "keyword": "hp",
      "category": "elektronik"
    },
    {
      "keyword": "handphone",
      "category": "elektronik"
    },
    {
      "keyword": "keyboard",
      "category": "elektronik"
    },
    {
      "keyword": "powerbank",
      "category": "elektronik"
    }
  ],
  "quiz": [
    {
      "question": "Kulit pisang masuk ke mana?",
      "options": [
        "Organik",
        "Residu",
        "Plastik"
      ],
      "answer": 0,
      "explanation": "Kulit pisang bahan alami dan bisa jadi kompos."
    },
    {
      "question": "Baterai bekas termasuk jenis apa?",
      "options": [
        "Elektronik biasa",
        "B3",
        "Residu"
      ],
      "answer": 1,
      "explanation": "Baterai mengandung logam berbahaya, jadi masuk B3."
    },
    {
      "question": "Tempat sampah warna biru untuk apa?",
      "options": [
        "Kertas",
        "Organik",
        "B3"
      ],
      "answer": 0,
      "explanation": "Biru untuk kertas: kardus, koran, buku bekas."
    },
    {
      "question": "Popok bekas masuk ke mana?",
      "options": [
        "Plastik",
        "Residu",
        "Kertas"
      ],
      "answer": 1,
      "explanation": "Popok sulit didaur ulang, jadi masuk Residu."
    },
    {
      "question": "Kabel charger rusak masuk ke mana?",
      "options": [
        "Elektronik",
        "Organik",
        "Plastik"
      ],
      "answer": 0,
      "explanation": "Kabel dan charger masuk e-waste."
    }
  ]
};
