<template>
  <el-row :gutter="20" class="card-container">
    <el-col :xs="24" :sm="12" :md="8" v-for="(link, index) in links" :key="index">
      <el-card 
        class="card-item" 
        shadow="hover"
        @click.native="navigateTo(link.url)"
      >
        {{ link.name }}
      </el-card>
    </el-col>
  </el-row>
</template>

<script>
export default {
  data () {
    return {
      links: [
        { name: '上传论文', url: '/upload' },
        { name: '论文管理', url: '/ThesisManagement' },
        { name: '申诉中心', url: '/appeals' },
        { name: '批量上传', url: '/feature5' },
        { name: '用户管理', url: '/users' },
        { name: '操作日志', url: '/operation-logs' }
      ]
    }
  },
  methods: {
    navigateTo(url) {
      console.log('Navigating to:', url)
      this.$router.push(url).catch(err => {
        if (err.name !== 'NavigationDuplicated') {
          console.error('Navigation error:', err)
        }
      })
    }
  }
}
</script>

<style scoped>
.card-container {
  padding: 30px;
  max-width: 1200px;
  margin: 0 auto;
}

.card-item {
  margin-bottom: 20px;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.card-item:hover {
  transform: translateY(-5px);
}

.el-card {
  text-align: center;
  font-size: 18px;
  font-weight: 500;
  color: #3aa9f2;
}

.el-card:hover {
  color: #2980b9;
}
</style>
