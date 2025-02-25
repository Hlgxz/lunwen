<template>
  <el-container class="upload-container">
    <!-- 上传区域 -->
    <el-card class="upload-card">
      <div slot="header">
        <h2>论文上传</h2>
      </div>
      
      <el-upload
        class="upload-area"
        drag
        :action="'/api/student/upload'"
        :on-success="handleUploadSuccess"
        :on-error="handleUploadError"
        :on-progress="handleProgress"
        :before-upload="beforeUpload"
        :show-file-list="false"
      >
        <i class="el-icon-upload"></i>
        <div class="el-upload__text">将文件拖到此处或<em>点击上传</em></div>
      </el-upload>

      <!-- 上传进度条 -->
      <el-progress 
        v-if="uploading" 
        :percentage="uploadProgress"
        :status="uploadProgress === 100 ? 'success' : ''"
      ></el-progress>
    </el-card>

    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="论文标题">
          <el-input v-model="searchForm.title" placeholder="请输入论文标题"></el-input>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="resetSearch">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格区域 -->
    <el-card class="table-card">
      <el-table :data="thesisList" style="width: 100%">
        <el-table-column prop="original_name" label="论文标题"></el-table-column>
        <el-table-column prop="username" label="作者"></el-table-column>
        <el-table-column label="提交时间">
          <template slot-scope="scope">
            {{ formatDate(scope.row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200">
          <template slot-scope="scope">
            <el-button 
              type="primary" 
              size="small" 
              @click="viewThesis(scope.row)"
            >下载</el-button>
            <el-button 
              v-if="scope.row.status === 'pending'"
              type="success" 
              size="small" 
              @click="AIThesis(scope.row)"
            >AI 检测</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination
          background
          layout="prev, pager, next"
          :current-page.sync="currentPage"
          :page-size="pageSize"
          :total="total"
          @current-change="handlePageChange"
        >
        </el-pagination>
      </div>
    </el-card>
  </el-container>
</template>

<script>
import axios from 'axios'
import { Message } from 'element-ui'

export default {
  data() {
    return {
      uploading: false,
      uploadProgress: 0,
      searchForm: {
        title: '',
        status: ''
      },
      thesisList: [],
      currentPage: 1,
      pageSize: 10,
      total: 0
    }
  },
  created() {
    this.fetchThesisList()
  },
  methods: {
    beforeUpload(file) {
      // 这里可以添加文件验证逻辑
      return true
    },
    handleUploadSuccess(response) {
      Message.success('上传成功')
      this.uploading = false
      this.uploadProgress = 100
      this.fetchThesisList()
    },
    handleUploadError() {
      Message.error('上传失败，请重试')
      this.uploading = false
    },
    handleProgress(event) {
      this.uploading = true
      this.uploadProgress = Math.round(event.percent)
    },
    handleSearch() {
      this.currentPage = 1
      this.fetchThesisList()
    },
    resetSearch() {
      this.searchForm = {
        title: '',
        status: ''
      }
      this.handleSearch()
    },
    async fetchThesisList() {
      try {
        const response = await axios.get('/api/student/thesis', {
          params: {
            page: this.currentPage,
            pageSize: this.pageSize,
            ...this.searchForm
          }
        })
        this.thesisList = response.data.items
        this.total = response.data.total
      } catch (error) {
        Message.error('获取论文列表失败')
      }
    },
    handlePageChange(page) {
      this.fetchThesisList()
    },
    viewThesis(thesis) {
      axios.get(`/api/student/thesis/${thesis.id}/download`, {
        responseType: 'blob'
      }).then(response => {
        const url = window.URL.createObjectURL(response.data)
        const a = document.createElement('a')
        a.href = url
        a.download = thesis.original_name
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        window.URL.revokeObjectURL(url)
      }).catch(() => {
        Message.error('下载失败，请重试')
      })
    },
    AIThesis(thesis) {
      axios.post(`/api/student/thesis/${thesis.id}/ai-check`).then(() => {
        Message.success('AI 检测完成')
      }).catch(error => {
        Message.error(error.response?.data?.message || 'AI 检测失败，请重试')
      })
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  }
}
</script>

<style scoped>
.upload-container {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.upload-card, .search-card, .table-card {
  width: 100%;
}

.upload-area {
  width: 100%;
}

.pagination-container {
  margin-top: 20px;
  text-align: center;
}

/* Element UI 上传组件的自定义样式 */
.el-upload {
  width: 100%;
}

.el-upload-dragger {
  width: 100%;
}

/* 表格内按钮间距 */
.el-button + .el-button {
  margin-left: 10px;
}
</style>