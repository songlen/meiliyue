;(function () {
  // 点击显示全部
  $(document).on('click', '.invitation .options .all', function () {
    $('.invitation .classify ul').height('toggle')
  })
  // 点击显示弹出层
  $('.plus').click(function () {
    $('.mask').toggle();
    $('._shade').toggle();
    $(this).css("transform", "rotate(45deg)");
  })
})()
