class GarmentTemplate {
  final String key;
  final String name;
  final String hindiName;
  final String icon;
  final List<String> fields;
  final Map<String, List<String>> styleOptions;

  const GarmentTemplate({
    required this.key,
    required this.name,
    required this.hindiName,
    required this.icon,
    required this.fields,
    required this.styleOptions,
  });
}

class GarmentTemplates {
  static const List<GarmentTemplate> all = [
    GarmentTemplate(
      key: 'shirt',
      name: 'Bespoke Formal Shirt',
      hindiName: 'शर्ट',
      icon: '👔',
      fields: [
        'Neck (गला)',
        'Chest (सीना)',
        'Waist (कमर)',
        'Hip (हिप)',
        'Shoulder (कंधा)',
        'Sleeve Length (आस्तीन)',
        'Bicep (डोला)',
        'Wrist (कलाई)',
        'Shirt Length (लंबाई)',
      ],
      styleOptions: {
        'Collar Type': ['Spread Collar', 'Semi-Cutaway', 'Classic Point', 'Mandarin / Bandhgala', 'Button Down'],
        'Cuff Style': ['Single Button Barrel', 'Double Button Barrel', 'French Cuff (Cufflinks)', 'Convertible'],
        'Pocket': ['Single Left Pocket', 'No Pocket', 'Dual Flap Pockets', 'V-Cut Pocket'],
        'Placket': ['Standard Front', 'French / Seamless Placket', 'Covered Placket (Tuxedo)'],
      },
    ),
    GarmentTemplate(
      key: 'trouser',
      name: 'Tailored Formal Trouser',
      hindiName: 'पैंट / ट्राउजर',
      icon: '👖',
      fields: [
        'Waist (कमर)',
        'Hip (हिप)',
        'Rise (आसन)',
        'Thigh (जांघ)',
        'Knee (घुटना)',
        'Calf (पिंडली)',
        'Bottom / Leg Opening (मोरी)',
        'Inseam (अंदरूनी लंबाई)',
        'Outseam (पूरी लंबाई)',
      ],
      styleOptions: {
        'Pleat Style': ['Flat Front (No Pleat)', 'Single Pleat', 'Double Pleats', 'Reverse Pleats'],
        'Waistband': ['Standard Belt Loops', 'Side Metal Adjusters (Gurkha)', 'Elastic Backband', 'Extended Tab'],
        'Bottom Finish': ['Plain Hem', 'Turn-Up Cuff 1.5 Inch', 'Turn-Up Cuff 2.0 Inch'],
        'Pocket Style': ['Slanted Side Pockets', 'Straight Seam Pockets', 'Double Welted Back'],
      },
    ),
    GarmentTemplate(
      key: 'kurta',
      name: 'Designer Kurta & Pajama',
      hindiName: 'कुर्ता & पजामा',
      icon: '👘',
      fields: [
        'Chest (सीना)',
        'Waist (कमर)',
        'Hip (हिप)',
        'Shoulder (कंधा)',
        'Sleeve Length (आस्तीन)',
        'Armhole (मुड्ढा)',
        'Kurta Length (कुर्ता लंबाई)',
        'Pajama Waist (पजामा कमर)',
        'Pajama Length (पजामा लंबाई)',
        'Pajama Bottom (मोरी)',
      ],
      styleOptions: {
        'Neck / Collar': ['Nehru Mandarin Collar', 'Angrakha Cut', 'Short Round Neck', 'V-Notch Placket'],
        'Kurta Hem': ['Straight Royal Hem', 'Round Apple Cut Hem', 'Asymmetrical High-Low'],
        'Pajama Style': ['Churidar Style', 'Straight Aligarh Pajama', 'Dhoti Pants', 'Salwar Style'],
      },
    ),
    GarmentTemplate(
      key: 'suit',
      name: 'Bespoke 2-Piece Suit / Blazer',
      hindiName: 'सूट / ब्लेज़र',
      icon: '🧥',
      fields: [
        'Chest (सीना)',
        'Overbust (ऊपरी सीना)',
        'Stomach / Waist (पेट/कमर)',
        'Hip (हिप)',
        'Shoulder (कंधा)',
        'Sleeve Length (आस्तीन)',
        'Bicep (डोला)',
        'Jacket Length (कोट लंबाई)',
        'Trouser Waist (पैंट कमर)',
        'Trouser Inseam (पैंट लंबाई)',
      ],
      styleOptions: {
        'Lapel Style': ['Notch Lapel (Classic)', 'Peak Lapel (Bold)', 'Shawl Lapel (Tuxedo)'],
        'Button Config': ['2-Button Single Breasted', '1-Button Single Breasted', '6-Button Double Breasted'],
        'Vents': ['Double Side Vents', 'Single Center Vent', 'No Vent (Italian)'],
        'Inner Lining': ['Full Silk Lining', 'Half Unconstructed Lining', 'Contrast Gold/Maroon Lining'],
      },
    ),
    GarmentTemplate(
      key: 'blouse',
      name: 'Designer Saree Blouse',
      hindiName: 'ब्लाउज',
      icon: '👚',
      fields: [
        'Bust (सीना)',
        'Underbust (अंडरबस्ट)',
        'Waist (कमर)',
        'Shoulder (कंधा)',
        'Sleeve Length (बांह लंबाई)',
        'Armhole (मुड्ढा)',
        'Front Neck Depth (आगे का गला)',
        'Back Neck Depth (पीछे का गला)',
        'Blouse Length (कुल लंबाई)',
      ],
      styleOptions: {
        'Cut Style': ['Princess Cut (Padded)', 'Princess Cut (Non-Padded)', 'Katori / 4-Tucks', 'Single Dart Sabyasachi'],
        'Neck Style': ['Deep Round Neck', 'Boat Neck', 'Sweetheart Neck', 'Backless with Dori', 'Collar Neck'],
        'Opening': ['Back Hooks with Latkan', 'Front Hooks with Placket', 'Side Concealed Zip'],
      },
    ),
    GarmentTemplate(
      key: 'kurti',
      name: 'Custom Kurti & Palazzo Set',
      hindiName: 'कुर्ती & प्लाजो',
      icon: '👗',
      fields: [
        'Bust (सीना)',
        'Waist (कमर)',
        'Hip (हिप)',
        'Shoulder (कंधा)',
        'Sleeve Length (बांह)',
        'Armhole (मुड्ढा)',
        'Front Neck (आगे का गला)',
        'Kurti Length (कुर्ती लंबाई)',
        'Palazzo Waist (प्लाजो कमर)',
        'Palazzo Length (प्लाजो लंबाई)',
      ],
      styleOptions: {
        'Silhouette': ['A-Line Flared', 'Straight Cut Slit', 'Anarkali Floor Length', 'Peplum Style'],
        'Sleeve Cut': ['Full Sleeve (Churidar)', '3/4th Sleeve', 'Cap Sleeve', 'Sleeveless with Border'],
        'Palazzo Style': ['Wide Flare Palazzo', 'Straight Cigarette Pants', 'Sharara Frills'],
      },
    ),
  ];

  static GarmentTemplate getByKey(String key) {
    return all.firstWhere(
      (t) => t.key.toLowerCase() == key.toLowerCase() || t.name.toLowerCase().contains(key.toLowerCase()),
      orElse: () => all[0],
    );
  }
}
