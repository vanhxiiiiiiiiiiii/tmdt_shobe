const categories = {
  'Audio': 'Âm thanh',
  'Beauty': 'Làm đẹp',
  'Clothes': 'Thời trang',
  'Computers & Accessories': 'Máy tính và phụ kiện',
  'Gaming & Consoles': 'Trò chơi và máy chơi game',
  'Health': 'Sức khỏe',
  'Home Appliances': 'Đồ gia dụng',
  'Mobile & Gadgets': 'Điện thoại và phụ kiện',
  'Pets': 'Thú cưng',
  'Stationery': 'Văn phòng phẩm',
}

export const categoryLabel = name => categories[name] ?? name
